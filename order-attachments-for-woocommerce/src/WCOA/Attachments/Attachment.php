<?php

namespace DirectSoftware\WCOA\Attachments;

use Automattic\WooCommerce\Utilities\OrderUtil;
use DirectSoftware\WCOA\Attachments\Data\AttachmentFile;
use DirectSoftware\WCOA\Attachments\Data\BaseFile;
use DirectSoftware\WCOA\Common\Notification;
use DirectSoftware\WCOA\Common\Options;
use DirectSoftware\WCOA\Exception\InvalidFileTypeException;
use DirectSoftware\WCOA\Kernel;
use DirectSoftware\WCOA\Utils\FileUtils;
use DirectSoftware\WCOA\Utils\Logger;

/**
 * @author d.gubala
 */
class Attachment
{
	private BaseFile $file;

	private int $order_id;

	private Kernel $kernel;
	private Logger $logger;

	public const META_KEY = "_wcoa_attachment_id";

	public function __construct(array $file, int $order_id)
	{
		$this->file = BaseFile::getInstance($file);
		$this->order_id = $order_id;
		$this->kernel = Kernel::getInstance();
		$this->logger = Logger::getInstance();
	}

	/**
	 * @throws InvalidFileTypeException
	 */
	public function save(): AttachmentFile|null
	{
		$this->logger->info('File save initiated.');
		$attachment = $this->upload();

		if ($attachment !== null)
		{
			$notification = new Notification($this->order_id, $attachment->getId());
			$notification->create_note();

			if (Options::get('email_enabled') === true)
			{
				$notification->send_email();
			}

			$this->logger->info('File save completed.');
			return $attachment;
		}

		$this->logger->error('File save failed.');
		return null;
	}

	/**
	 * @return AttachmentFile|null
	 * @throws InvalidFileTypeException
	 */
	private function upload(): AttachmentFile|null
	{
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		// check if the file exists
		if ($this->file->isEmpty())
		{
			$this->logger->warning('No file to upload.', $this->file->getOriginal());
			return null;
		}

		$this->file->setPrefix($this->get_attachment_prefix());

		// get the file type from the file name
		$file_type = wp_check_filetype($this->file->getBasename());
		FileUtils::checkType($file_type);

		// create file in the upload folder
		$attachment = wp_upload_bits($this->file->getName(), null, file_get_contents($this->file->getTemporaryName()));

		$filename = $attachment['file'];

		$title = preg_replace('/\.[^.]+$/', '', basename($filename));

		$metadata = [
			'post_mime_type' => $file_type['type'],
			'post_title' => $title,
			'post_content' => '',
			'post_status' => 'inherit',
			'guid' => $attachment['url']
		];

		// insert attachment to database and return attachment id
		$attachment_id = wp_insert_attachment($metadata, $attachment['url'] );

		$inserted_metadata = wp_generate_attachment_metadata($attachment_id, $filename);

		wp_update_attachment_metadata($attachment_id, $inserted_metadata);

		if ($this->kernel->hpos_is_enabled() && OrderUtil::get_order_type($this->order_id) === 'shop_order')
		{
			$order = wc_get_order($this->order_id);
			if ($order)
			{
				$order->add_meta_data(self::META_KEY, $attachment_id);
				$order->save();
			}
		}
		else
		{
			add_post_meta($this->order_id, self::META_KEY, $attachment_id);
		}

		$result = new AttachmentFile(
			$attachment_id,
			$title,
			$attachment['url']
		);

		$this->logger->info('File upload result:', $result->toArray());

		return $result;
	}

	/**
	 * @since 2.2.2
	 * @param int $order_id
	 * @return array
	 */
	public static function get_all_by_order(int $order_id): array
	{
		global $wpdb;

		if (Kernel::getInstance()->hpos_is_enabled())
		{
			$command = sprintf("SELECT attachment.post_title, attachment.guid 
                                   FROM %swc_orders_meta AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND attachment_meta.order_id = %d;",
				$wpdb->prefix, $wpdb->posts, $order_id);
		}
		else
		{
			$command = sprintf("SELECT attachment.post_title, attachment.guid 
                                   FROM %s AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND attachment_meta.post_id = %d;",
				$wpdb->postmeta, $wpdb->posts, $order_id);
		}

		$result = $wpdb->get_results($command, ARRAY_A);

		return empty($result) ? [] : $result;
	}

	/**
	 * @since 2.2.2
	 * @param int $user_id
	 * @return array
	 */
	public static function get_all_by_user(int $user_id): array
	{
		global $wpdb;

		if (Kernel::getInstance()->hpos_is_enabled())
		{
			$command = sprintf("SELECT 
                                   attachment.ID AS attachment_id,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.order_id AS order_id,
                                   attachment.guid,
                                   user_order.customer_id
                                   FROM %swc_orders_meta AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   JOIN %swc_orders AS user_order ON attachment_meta.order_id = user_order.id
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND user_order.customer_id = %d;",
				$wpdb->prefix, $wpdb->posts, $wpdb->prefix, $user_id);
		}
		else
		{
			$command = sprintf("SELECT 
                                   attachment.ID AS attachment_id,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.post_id AS order_id,
                                   attachment.guid,
                                   user_order.post_author
                                   FROM %s AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   JOIN %s AS user_order ON attachment_meta.post_id = user_order.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND user_order.post_author = %d;",
				$wpdb->postmeta, $wpdb->posts, $wpdb->posts, $user_id);
		}

		$result = $wpdb->get_results($command, ARRAY_A);

		return empty($result) ? [] : $result;
	}

	/**
	 * @since 2.2.2
	 * @return array
	 */
	public static function get_all(): array
	{
		global $wpdb;

		if (Kernel::getInstance()->hpos_is_enabled())
		{
			$command = sprintf("SELECT
                                   attachment.ID AS attachment_id,
                                   attachment.post_author,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.order_id AS order_id,
                                   attachment.guid FROM %swc_orders_meta AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment';",
				$wpdb->prefix, $wpdb->posts);
		}
		else
		{
			$command = sprintf("SELECT
                                   attachment.ID AS attachment_id,
                                   attachment.post_author,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.post_id AS order_id,
                                   attachment.guid FROM %s AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment';",
				$wpdb->postmeta, $wpdb->posts);
		}

		$result = $wpdb->get_results($command, ARRAY_A);

		return empty($result) ? [] : $result;
	}

	public static function get_list(int $order_id = 0, int $user_id = 0): array
	{
		if ($user_id !== 0)
		{
			return self::prepare_customer_list($user_id);
		}

		return self::prepare_list($order_id);
	}

	private static function prepare_list(int $order_id = 0): array
	{
		global $wpdb;

		$sql_check = "SELECT COUNT(*) FROM $wpdb->postmeta AS order_meta
				JOIN $wpdb->postmeta AS attachment_meta ON order_meta.meta_value = attachment_meta.post_id
				JOIN $wpdb->posts AS attachments ON order_meta.meta_value = attachments.ID
				WHERE order_meta.meta_key = '_wcoa_attachment_id' and attachment_meta.meta_key = '_wp_attached_file'";

		if ($order_id !== 0)
		{
			$sql_check .= " AND order_meta.post_id = $order_id;";
		}

		$counter = $wpdb->get_var($sql_check);

		if ($counter <= 0)
		{
			return [];
		}

		$sql = "SELECT order_meta.post_id AS order_id, order_meta.meta_value AS post_id, attachments.post_author,
       			attachments.post_date, attachments.post_title, attachments.guid FROM $wpdb->postmeta AS order_meta
				JOIN $wpdb->postmeta AS attachment_meta ON order_meta.meta_value = attachment_meta.post_id
				JOIN $wpdb->posts AS attachments ON order_meta.meta_value = attachments.ID
				WHERE order_meta.meta_key = '_wcoa_attachment_id' AND attachment_meta.meta_key = '_wp_attached_file'";

		if ($order_id !== 0)
		{
			$sql .= " AND order_meta.post_id = $order_id";
		}

		return $wpdb->get_results($sql, ARRAY_A);

	}

	public static function prepare_customer_list(int $user_id): array
	{
		global $wpdb;

		$sql_check = sprintf("SELECT COUNT(*) FROM %s AS attachment
                      JOIN %s AS attachment_meta ON (attachment_meta.meta_value = attachment.ID)
                      JOIN %s AS orders ON attachment_meta.post_id = orders.post_id
                      WHERE attachment.post_type = 'attachment' AND attachment_meta.meta_key = '_wcoa_attachment_id'
                      AND orders.meta_key = '_customer_user' AND attachment.post_mime_type != '' AND orders.meta_value = %d;",
			$wpdb->posts, $wpdb->postmeta, $wpdb->postmeta, $user_id);

		$counter = $wpdb->get_var($sql_check);

		if (is_numeric($counter) && (int)$counter <= 0)
		{
			return [];
		}

		$sql = sprintf("SELECT orders.post_id AS 'order_id', attachment.post_date, attachment.guid 
				FROM %s AS attachment
                JOIN %s AS attachment_meta ON (attachment_meta.meta_value = attachment.ID)
                JOIN %s AS orders ON attachment_meta.post_id = orders.post_id
                WHERE attachment.post_type = 'attachment' AND attachment_meta.meta_key = '_wcoa_attachment_id'
                AND orders.meta_key = '_customer_user' AND attachment.post_mime_type != '' AND orders.meta_value = %d;",
			$wpdb->posts, $wpdb->postmeta, $wpdb->postmeta, $user_id);

		return $wpdb->get_results($sql, ARRAY_A);

	}

	public static function get_url(int $order_id, string $order_key, int $attachment_id = 0): bool|string
	{
		global $wpdb;

		$logger = Logger::getInstance();

		if (Kernel::getInstance()->hpos_is_enabled())
		{
			$command = sprintf(
				"SELECT COUNT(*) FROM %swc_order_operational_data where order_id = %d and order_key = '%s'",
				$wpdb->prefix, $order_id, $order_key
			);
			$counter = $wpdb->get_var($command);
		}
		else
		{
			$counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = $order_id AND meta_value = '$order_key';");
		}

		if ($counter < 1)
		{
			$logger->warning("Order validation failed: no matching records found for order ID $order_id and order key $order_key.");
			return false;
		}

		if ($attachment_id === 0)
		{
			$result = $wpdb->get_var("SELECT MIN(meta_value) AS qty FROM $wpdb->postmeta where post_id = $order_id and meta_key = '_wcoa_attachment_id'");
			if (!is_numeric($result) )
			{
				$logger->warning("Invalid or missing attachment ID for order ID $order_id.");
				return false;
			}
			$attachment_id = (int)$result;
		}

		$url = get_the_guid($attachment_id);

		if ($url === '' || str_contains($url, '?p=1'))
		{
			$logger->warning("Invalid or empty URL detected, processing halted.");
			return false;
		}

		return $url;
	}

	public static function get_public_url(int $order_id, int $attachment_id): string
	{
		global $wpdb;

		if (Kernel::getInstance()->hpos_is_enabled())
		{
			$command = sprintf("SELECT order_key FROM %swc_order_operational_data where order_id = %d", $wpdb->prefix, $order_id);
			$key = $wpdb->get_var($command);
		}
		else
		{
			$key = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $order_id AND meta_key = '_order_key'");
		}

		if (empty($key))
		{
			Logger::getInstance()->warning(
				"WC Key not found for the given parameters.",
				['order_id' => $order_id, 'attachment_id' => $attachment_id]
			);
			return false;
		}

		$path = "?wcoa_attachment_for_order=$order_id&key=$key&id=$attachment_id";

		return get_home_url(null, $path);
	}

	/**
	 * @since 2.2.0
	 * @return string
	 */
	private function get_attachment_prefix(): string
	{
		$default = '';

		if (get_option('wcoa_general'))
		{
			$prefix = get_option( 'wcoa_general')['attachment_prefix'] ?? '';

			if (str_contains($prefix, "{order_number}"))
			{
				return str_replace("{order_number}", $this->order_id, $prefix);
			}

			return empty($prefix) ? $default : trim($prefix);
		}

		return $default;
	}
}