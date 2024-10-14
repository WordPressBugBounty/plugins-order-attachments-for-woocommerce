<?php

namespace DirectSoftware\WCOA\Utils;

/**
 * @author d.gubala
 */
class MyAccount
{
	private static ?MyAccount $instance = null;

	private string $attachments_url;

	public static function getInstance(): MyAccount
	{
		if (self::$instance === null)
		{
			self::$instance = new MyAccount();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->attachments_url = $this->get_attachments_endpoint_url();
		add_action('init', [$this, 'add_rule']);
		add_filter('query_vars', [$this, 'whitelist_update'], 0);
		add_filter('woocommerce_account_menu_items', [$this, 'initialize_menu']);
		add_action('woocommerce_account_wcoa-attachments_endpoint', [$this, 'initialize_template']);
		add_action('wp_enqueue_scripts', [$this, 'load_front_css']);
	}

	public function add_rule(): void
	{
		add_rewrite_endpoint( $this->attachments_url, EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}

	public function load_front_css(): void
	{
		wp_enqueue_style( 'wcoa-front-app', WCOA_PLUGIN_URL . '/assets/frontend/css/style.css', false, WCOA_PLUGIN_VERSION);
	}

	public function whitelist_update(array $vars): array
	{
		$vars[] = $this->attachments_url;
		return $vars;
	}

	public function initialize_menu($items): array
	{
		$attachments = [$this->attachments_url => __("Attachments", 'sld-wcoa')];

		$counter = count($items);

		return array_slice($items,0,$counter -1,true )
		       + $attachments
		       + array_slice($items,1,NULL,true );
	}

	public function initialize_template(): void
	{
		require_once WCOA_PLUGIN_DIR . 'templates/frontend/frontend-my-account-attachments.php';
	}

	/**
	 * @since 2.2.0
	 * @return string
	 */
	private function get_attachments_endpoint_url(): string
	{
		$default = 'wcoa-attachments';

		if (get_option('wcoa_general'))
		{
			$endpoint = get_option( 'wcoa_general')['attachments_endpoint'] ?? null;

			return empty($endpoint) ? $default : trim($endpoint);
		}

		return $default;
	}
}