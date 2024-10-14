<?php

namespace DirectSoftware\WCOA;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use DirectSoftware\WCOA\Attachments\Attachment;
use DirectSoftware\WCOA\Attachments\RedirectionHandler;
use DirectSoftware\WCOA\Common\Notification;
use DirectSoftware\WCOA\Common\Options;
use DirectSoftware\WCOA\Functions\DisplayAllAttachmentsAsShortcode;
use DirectSoftware\WCOA\Utils\Ajax;
use DirectSoftware\WCOA\Utils\Logger;
use DirectSoftware\WCOA\Utils\MyAccount;
use Throwable;

/**
 * @author d.gubala
 */
class Kernel
{
    public const SHORTCODE_DISPLAY_ALL_ATTACHMENTS = "wcoa_display_all_attachments";

	private static ?Kernel $instance = null;

	private bool $hpos_is_enabled;

	private function __construct()
	{
		$this->init();
		add_action('admin_menu', [$this, 'init_menu_page']);
		add_action('before_woocommerce_init', [$this, 'verify_hpos']);
		add_action('add_meta_boxes', [$this, 'init_meta_box']);
		add_action('admin_footer', [$this, 'load_js']);
		add_action('admin_footer', [$this, 'load_ajax_nonce']);
		add_action('admin_enqueue_scripts', [$this, 'load_css']);
		add_filter('plugin_action_links_' . WCOA_BASENAME, [$this, 'plugin_donate_link']);
		add_filter('plugin_action_links_' . WCOA_BASENAME, [$this, 'plugin_settings_link']);
		add_action('delete_attachment', [$this, 'erase_metadata_after_delete_attachment']);
		add_action('admin_post_wcoa_delete', [$this, 'delete_attachment']);
		add_action('woocommerce_email_before_order_table', [$this, 'attachment_details']);
	}

	public static function getInstance(): Kernel
	{
		if (self::$instance === null)
		{
			self::$instance = new Kernel();
		}

		return self::$instance;
	}

	public function init(): void
	{
		try
		{
			Ajax::getInstance();
			MyAccount::getInstance();
			Options::getInstance();
			RedirectionHandler::getInstance();

			add_shortcode( self::SHORTCODE_DISPLAY_ALL_ATTACHMENTS, new DisplayAllAttachmentsAsShortcode);
        }
        catch (Throwable $e)
        {
            Logger::getInstance()->error("An error occurred during plugin execution.", $e->getTrace());
        }
	}

	public static function load_js(): void
	{
		wp_enqueue_script(
                'wcoa-app',
                plugins_url('../../assets/admin/js/wcoa.js', __FILE__),
            [],
			WCOA_PLUGIN_VERSION
        );
	}

    public static function load_ajax_nonce(): void
    {
	    $nonce = wp_create_nonce(Ajax::ADD_ATTACHMENT_ACTION);
		echo sprintf("<script id=\"wcoa-common\">const %s = \"%s\";</script>", Ajax::ADD_ATTACHMENT_NONCE, $nonce);
	}

	public static function init_menu_page(): void
	{
		add_menu_page(
			__('Attachments','sld-wcoa'),
			__('Attachments','sld-wcoa'),
			'edit_others_shop_orders',
			'wcoa',
			[self::class, 'add_menu_page_content'],
			'dashicons-paperclip',
			20
		);
	}

	public static function add_menu_page_content(): void
	{
		require_once WCOA_PLUGIN_DIR . 'templates/admin/admin-main-page-content.php';
	}

	public static function load_css(): void
	{
		wp_enqueue_style(
                'wcoa-app',
                plugins_url( '../../assets/admin/css/style.css' , __FILE__ ),
            [],
			WCOA_PLUGIN_VERSION
        );
	}

	public static function init_meta_box(): void
	{
		$screen = class_exists( CustomOrdersTableController::class ) && wc_get_container()
			->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id('shop-order')
			: 'shop_order';

		add_meta_box(
			'wcoa_meta_box',
			__('Attachment','sld-wcoa'),
			[ __CLASS__, 'add_meta_box_content'],
			$screen,
			'side',
			'core'
		);
	}

	public static function add_meta_box_content(): void
	{
		require_once WCOA_PLUGIN_DIR . 'templates/admin/admin-order-meta-box-content.php';
	}

	public function plugin_settings_link(array $links): array
	{
		$url = admin_url()."admin.php?page=wcoa&tab=settings";
		$settings_link = '<a href="' . esc_url( $url ) . '">' . __( 'Settings' ) . '</a>';
		$links[] = $settings_link;
		return $links;
	}

	public function plugin_donate_link(array $links): array
	{
		$url = "https://ko-fi.com/directsoftware";
		$settings_link = '<a target="_blank" href="' . esc_url( $url ) . '">' . __( 'Treat us to coffee', 'sld-wcoa' ) . '</a>';
		$links[] = $settings_link;
		return $links;
	}

	public static function erase_metadata_after_delete_attachment(int $attachment_id): void
	{
		global $wpdb;

		$order_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wcoa_attachment_id' AND meta_value = '$attachment_id'");

		delete_post_meta($order_id, Attachment::META_KEY, $attachment_id);
	}

	public static function activation_task(): void
	{
        Logger::getInstance()->info("Plugin activated successfully.");
		flush_rewrite_rules();
	}

	public static function delete_attachment(): void
	{
		$attachment_id = 0;

		if (isset($_GET['attachment_id']) && $_GET['attachment_id'] !== '')
		{
			$attachment_id = $_GET['attachment_id'];
		}

		check_admin_referer('wcoa_delete_' . $attachment_id);

		wp_delete_attachment($attachment_id, true);

        Logger::getInstance()->info(sprintf("Deleted attachment with ID %d by user with ID %s.", $attachment_id, get_current_user_id()));

		setcookie('wcoa_deleted', 1, time() + 30);

		wp_redirect( wp_get_referer() );
	}

	public static function attachment_details($order): void
	{
		$order_id = $order->get_id();

		if (!$order->has_status('completed') || !Options::get('completed_email_enabled'))
		{
			return;
		}

		$attachments = Attachment::get_list($order_id);

		if (count($attachments) === 0)
		{
			return;
		}

		$notification = new Notification($order_id);

		print '<div>';
		print '<p>' . $notification->get_email_content() . '</p>';

		print '<table>';
		print '<tr>';
		print '<th>' .  __('Name') . '</th>' . '<th>' . __('Actions') . '</th>';
		print '</tr>';
		foreach ($attachments as $attachment)
		{
			print '<tr>';
			print '<td><span>' . $attachment['post_title'] . '</span></td>';
			print '<td><a class="button" href="' . $attachment['guid'] . '">' . __( 'View' ) . '</a></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
	}

	public static function notice_success_deleted(): void
	{ ?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e( 'Media file permanently deleted.' ); ?></p>
		</div>
		<?php
	}

	/**
	 * @since 2.2.0
	 * @return void
	 */
	public function verify_hpos(): void
	{
		if (class_exists( OrderUtil::class))
		{
			$this->hpos_is_enabled = OrderUtil::custom_orders_table_usage_is_enabled();
		}

		if (class_exists( FeaturesUtil::class))
		{
			FeaturesUtil::declare_compatibility('custom_order_tables', WCOA_PLUGIN_PATH );
		}
	}

	public function hpos_is_enabled(): bool
	{
		return $this->hpos_is_enabled;
	}
}