<?php

namespace DirectSoftware\WCOA\Utils;

use DirectSoftware\WCOA\Attachments\Attachment;
use DirectSoftware\WCOA\Common\Notification;
use DirectSoftware\WCOA\Exception\AjaxVerificationFailedException;
use DirectSoftware\WCOA\Exception\InvalidFileTypeException;
use DirectSoftware\WCOA\Exception\NoFileUploadedException;
use DirectSoftware\WCOA\Exception\PermissionDeniedException;
use Exception;
use JsonException;

/**
 * @author d.gubala
 */
class Ajax
{
	/** @since 2.5.0 */
	public const ADD_ATTACHMENT_ACTION = "wcoa_add_attachment";

	/** @since 2.5.0 */
	public const ADD_ATTACHMENT_NONCE = "wcoa_add_attachment_nonce";

	private static ?Ajax $instance = null;

	public static function getInstance(): Ajax
	{
		if (self::$instance === null)
		{
			self::$instance = new Ajax();
		}

		return self::$instance;
	}

	private function __construct()
	{
		add_action( 'wp_ajax_wcoa_add_attachment', [$this, 'add_attachment']);
		add_action( 'wp_ajax_wcoa_send_email_to_customer', [$this, 'send_email_to_customer']);
	}

	public static function add_attachment(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		try
		{
			$response = new JsonResponse(
				__('Error saving media file.'),
				false
			);

			try
			{
				if (check_ajax_referer(self::ADD_ATTACHMENT_ACTION, self::ADD_ATTACHMENT_NONCE, false) === false)
				{
					throw new AjaxVerificationFailedException();
				}

				if (isset($_FILES['attachment']['name']))
				{
					$file = $_FILES['attachment'];
					$order_id = $_POST['order_id'];

					if (!current_user_can('edit_post', $order_id))
					{
						throw new PermissionDeniedException();
					}

					$attachment = new Attachment($file, $order_id);
					$result = $attachment->save();

					if ($result !== null)
					{
						$response = new JsonResponse(
							sprintf(__('%s media file attached.'), 1),
							true,
							$result
						);
					}
				}
				else
				{
					throw new NoFileUploadedException();
				}
			}
			catch (AjaxVerificationFailedException)
			{
				$response->setMessage(sprintf(__('The authenticity of %s could not be verified.'), 'nonce'));
			}
			catch (PermissionDeniedException)
			{
				$response->setMessage(__('Sorry, you are not allowed to perform this action.'));
			}
			catch (InvalidFileTypeException)
			{
				$response->setMessage(__('File upload stopped by extension.'));
			}
			catch (NoFileUploadedException)
			{
				$response->setMessage( __('No file was uploaded.') );
			}
			catch (Exception $e)
			{
				$response->setMessage(__('Error saving media file.'));
				Logger::getInstance()->error("An error occurred during upload attachment.", $e->getTrace());
			}

			print json_encode($response, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $e)
		{
			print JsonResponse::pure(true, $e->getMessage());
		}

		wp_die();
	}

	public static function send_email_to_customer(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$response = new JsonResponse(
			'',
			false
		);

		$attachment_id = -1;

		if (isset($_POST['order_id']))
		{
			$attachment_id = 0;

			if (isset($_POST['attachment_id']))
			{
				$res = $_POST['attachment_id'];
				if (is_numeric($res))
				{
					$attachment_id = (int)$res;
				}
			}

			$notification = new Notification($_POST['order_id'], $attachment_id);
			$notification->send_email();

			$response->setSuccess(true);
		}

		Logger::getInstance()->info(sprintf("Notification sent for attachment ID %s related to order ID %s.", $attachment_id, $_POST['order_id']), $response->toArray());

		try
		{
			print json_encode($response, JSON_THROW_ON_ERROR);
		}
		catch (JsonException)
		{
			print JsonResponse::pure(true);
		}

		wp_die();
	}
}