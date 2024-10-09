<?php

namespace DirectSoftware\WCOA\Attachments;

use JetBrains\PhpStorm\NoReturn;

/**
 * @author d.gubala
 */
class RedirectionHandler
{
	private static ?RedirectionHandler $instance = null;

	public static function getInstance(): RedirectionHandler
	{
		if (self::$instance === null)
		{
			self::$instance = new RedirectionHandler();
		}

		return self::$instance;
	}

	private function __construct()
	{
		add_action( 'init', [$this, 'add_rule'] );
		add_filter( 'query_vars', [$this, 'whitelist_update']);
		add_action( 'template_include', [$this, 'initialize_template']);
	}

	public function add_rule(): void
	{
		add_rewrite_rule( 'wcoa_attachment_for_order/([a-z0-9-]+)[/]?$', 'index.php?wcoa_attachment_for_order=$matches[1]', 'top' );
	}

	public function whitelist_update($query_vars)
	{
		$query_vars[] = 'wcoa_attachment_for_order';
		return $query_vars;
	}

	public function initialize_template($template)
	{
		if (!get_query_var('wcoa_attachment_for_order' ) || get_query_var('wcoa_attachment_for_order') === '')
		{
			return $template;
		}

		return WCOA_PLUGIN_DIR . 'templates/frontend/frontend-redirect-handler.php';
	}

	public static function bad_request(): bool
	{
		header("HTTP/1.1 400 Bad Request");
		_e('Invalid URL.');
		return false;
	}

	#[NoReturn]
	public static function redirect(string $url): void
	{
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		exit();
	}

}