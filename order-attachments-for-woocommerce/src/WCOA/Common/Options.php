<?php

namespace DirectSoftware\WCOA\Common;

use DirectSoftware\WCOA\Kernel;

/**
 * @author d.gubala
 */
class Options
{
	public const OPTION_MESSAGE_HEADER = 'message_header';
	public const OPTION_MESSAGE_CONTENT = 'message_content';
	public const OPTION_BUTTON_TEXT = 'button_text';
	public const OPTION_ATTACHMENT_PREFIX = 'attachment_prefix';
	public const OPTION_ATTACHMENT_ENDPOINT = 'attachments_endpoint';
	public const OPTION_EMAIL_ENABLED = 'email_enabled';
	public const OPTION_COMPLETED_EMAIL_ENABLED = 'completed_email_enabled';
	public const OPTION_DISPLAY_ATTACHMENT_NAME = 'display_attachment_name';
	public const OPTION_ENABLE_LOGGING = 'enable_logging';

	private static ?Options $instance = null;

	private ?array $options;

	public static function getInstance(): Options
	{
		if (self::$instance === null)
		{
			self::$instance = new Options();
		}

		return self::$instance;
	}

	private function __construct()
	{
		add_action( 'admin_init', [$this, 'load_fields'] );

		$general = get_option( 'wcoa_general');
		$this->options = is_array($general) ? $general : null;
	}

	public function load_fields(): void
	{
		register_setting( 'wcoa_general', 'wcoa_general', [$this, 'validate'] );

		add_settings_section( 'general', __('General','woocommerce'), [], 'wcoa_general_page' );
		add_settings_section( 'email_template', __('Email options','woocommerce'), [$this, 'section_email_template_description'], 'wcoa_general_page' );
		add_settings_section( 'my_account', __('My account page','woocommerce'), [], 'wcoa_general_page' );

		add_settings_field( 'option_attachment_prefix', __('Attachment prefix','sld-wcoa'), [$this, 'option_input_text'], 'wcoa_general_page', 'general', self::params_attachment_prefix());
		add_settings_field( 'option_enable_logging', __('Enable Logging','sld-wcoa'), [$this, 'option_enable_logging'], 'wcoa_general_page', 'general' );

		add_settings_field('option_email_header', __('Subject','woocommerce'), [$this, 'option_input_text'], 'wcoa_general_page', 'email_template', self::params_email_header());
		add_settings_field( 'option_email_content', __('Message','woocommerce'), [$this, 'option_input_text'], 'wcoa_general_page', 'email_template', self::params_email_content());
		add_settings_field( 'option_email_button', __('Button text','woocommerce'), [$this, 'option_input_text'], 'wcoa_general_page', 'email_template', self::params_email_button());
		add_settings_field( 'option_email_enabled', __('Automatic email notifications','sld-wcoa'), [$this, 'option_email_enabled'], 'wcoa_general_page', 'email_template' );
		add_settings_field( 'option_completed_email_enabled', __('Completed order email','sld-wcoa'), [$this, 'option_completed_email_enabled'], 'wcoa_general_page', 'email_template' );

		add_settings_field( 'option_attachments_endpoint', __('Endpoint URL','sld-wcoa'), [$this, 'option_input_text'], 'wcoa_general_page', 'my_account', self::params_attachments_endpoint());
		add_settings_field( 'option_display_attachment_name', __('Display attachment name','sld-wcoa'), [$this, 'option_display_attachment_name'], 'wcoa_general_page', 'my_account' );
		add_settings_field( 'option_display_attachment_shortcodes', __('Shortcodes'), [$this, 'option_display_shortcodes'], 'wcoa_general_page', 'my_account' );

	}

	public function section_email_template_description(): void
	{
		_e('Customizing attachments email notifications.','sld-wcoa');
	}

	private static function params_email_header(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_email_header',
			'name' => self::OPTION_MESSAGE_HEADER,
			'style' => [ 'wcoa-input-400' ],
			'value' => self::OPTION_MESSAGE_HEADER,
			'placeholder' => 'New attachment to the order {order_number}',
			'label' => esc_html__('Text to appear the email subject.', 'sld-wcoa')
			           . " " . sprintf( esc_html__('Available placeholders: %s', 'woocommerce'), '<code>{site_title}</code>, <code>{order_number}</code>')
		];
	}

	private static function params_email_content(): array
	{
		return [
			'tag' => 'textarea',
			'id' => 'option_email_content',
			'name' => self::OPTION_MESSAGE_CONTENT,
			'style' => [ 'wcoa-input-400' ],
			'value' => self::OPTION_MESSAGE_CONTENT,
			'placeholder' => 'An attachment has been added to your order {order_number}. Click the button below to view it.',
			'label' => esc_html__('Text to appear the main email content.', 'sld-wcoa')
			           . " " . sprintf( esc_html__('Available placeholders: %s', 'woocommerce'), '<code>{site_title}</code>, <code>{order_number}</code>')
		];
	}

	private static function params_email_button(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_email_button',
			'name' => self::OPTION_BUTTON_TEXT,
			'style' => [ 'wcoa-input-200' ],
			'value' => self::OPTION_BUTTON_TEXT,
			'placeholder' => esc_html__('Preview', 'woocommerce'),
			'label' => esc_html__('Attachment preview button text.', 'sld-wcoa')
		];
	}

	/**
	 * @since 2.2.0
	 * @return array
	 */
	private static function params_attachment_prefix(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_attachment_prefix',
			'name' => self::OPTION_ATTACHMENT_PREFIX,
			'style' => [ 'wcoa-input-200' ],
			'value' => self::OPTION_ATTACHMENT_PREFIX,
			'placeholder' => '',
			'label' => esc_html__('The prefix will be added to the attachment file name.', 'sld-wcoa')
			           // translators: %s is the mapping type or description.
			           . " " . sprintf( esc_html__('Available mapping: %s', 'sld-wcoa'), '<code>{order_number}</code>')
		];
	}

	/**
	 * @since 2.2.0
	 * @return array
	 */
	private static function params_attachments_endpoint(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_attachments_endpoint',
			'name' => self::OPTION_ATTACHMENT_ENDPOINT,
			'style' => [ 'wcoa-input-200' ],
			'value' => self::OPTION_ATTACHMENT_ENDPOINT,
			'placeholder' => 'wcoa-attachments',
			'label' => esc_html__('Endpoint for the "My account &rarr; Attachments" page.', 'sld-wcoa')
		];
	}

	public function option_input_text(array $args): void
	{
		$style = implode(' ', $args['style']);

		$content = null;

		if (isset($this->options[$args['value']]))
		{
			$content = esc_attr( $this->options[$args['value']] );
		}

		print "<{$args['tag']} id='{$args['id']}' name='wcoa_general[{$args['name']}]' class='$style' type='text' value='$content' placeholder='{$args['placeholder']}' >";

		if ($args['tag'] === 'textarea')
		{
			print "$content</{$args['tag']}>";
		}

		print "<p for='{$args['id']}'>" . $args['label'] ."</p>";
	}

	/**
	 * @since 2.4.0
	 * @return void
	 */
	public function option_enable_logging(): void
	{
		$content = null;

		if (isset($this->options[self::OPTION_ENABLE_LOGGING]) && $this->options[self::OPTION_ENABLE_LOGGING] === true)
		{
			$content = 'checked';
		}

		print "<input id='option_enable_logging' name='wcoa_general[enable_logging]' type='checkbox' value='1' $content/>";
		print "<label for='option_enable_logging'>" . esc_html__("Enable logging to file for plugin events and errors.", 'sld-wcoa') ."</label>";
	}

	/**
	 * @since 2.4.0
	 * @return void
	 */
	public function option_display_attachment_name(): void
	{
		$content = null;

		if (isset($this->options[self::OPTION_DISPLAY_ATTACHMENT_NAME]) && $this->options[self::OPTION_DISPLAY_ATTACHMENT_NAME] === true)
		{
			$content = 'checked';
		}

		print "<input id='option_display_attachment_name' name='wcoa_general[display_attachment_name]' type='checkbox' value='1' $content/>";
		print "<label for='option_display_attachment_name'>" . esc_html__("Display attachment name on 'My Account → Attachments' page.", 'sld-wcoa') ."</label>";
	}

	/**
	 * @since 2.5.0
	 * @return void
	 */
	public function option_display_shortcodes(): void
	{
		print '<code>[' . Kernel::SHORTCODE_DISPLAY_ALL_ATTACHMENTS . ']</code>';
	}

	public function option_email_enabled(): void
	{
		$content = null;

		if (isset($this->options[self::OPTION_EMAIL_ENABLED]) && $this->options[self::OPTION_EMAIL_ENABLED] === true)
		{
			$content = 'checked';
		}

		print "<input id='option_email_enabled' name='wcoa_general[email_enabled]' type='checkbox' value='1' $content/>";
		print "<label for='option_email_enabled'>" . esc_html__("Enable auto-send messages after addition attachment.", 'sld-wcoa') ."</label>";
	}

	public function option_completed_email_enabled(): void
	{
		$content = null;

		if (isset($this->options[self::OPTION_COMPLETED_EMAIL_ENABLED]) && $this->options[self::OPTION_COMPLETED_EMAIL_ENABLED] === true)
		{
			$content = 'checked';
		}

		print "<input id='option_completed_email_enabled' name='wcoa_general[completed_email_enabled]' type='checkbox' value='1' $content/>";
		print "<label for='option_completed_email_enabled'>" . esc_html__("Add attachment information to completed order email.", 'sld-wcoa') ."</label>";
	}

	public function validate(array $input): array
	{
		$new = [];

		if (isset($input[self::OPTION_MESSAGE_HEADER]))
		{
			$new[self::OPTION_MESSAGE_HEADER] = trim( $input[self::OPTION_MESSAGE_HEADER]);
		}

		if (isset($input[self::OPTION_MESSAGE_CONTENT]))
		{
			$new[self::OPTION_MESSAGE_CONTENT] = trim( $input[self::OPTION_MESSAGE_CONTENT]);
		}

		if (isset($input[self::OPTION_BUTTON_TEXT]))
		{
			$new[self::OPTION_BUTTON_TEXT] = trim( $input[self::OPTION_BUTTON_TEXT]);
		}

		if (isset($input[self::OPTION_ATTACHMENT_PREFIX]))
		{
			$new[self::OPTION_ATTACHMENT_PREFIX] = trim( $input[self::OPTION_ATTACHMENT_PREFIX]);
		}

		if (isset($input[self::OPTION_ATTACHMENT_ENDPOINT]))
		{
			$new[self::OPTION_ATTACHMENT_ENDPOINT] = trim( $input[self::OPTION_ATTACHMENT_ENDPOINT]);
		}

		$new[self::OPTION_EMAIL_ENABLED] = isset($input[self::OPTION_EMAIL_ENABLED]) && trim($input[self::OPTION_EMAIL_ENABLED]) === '1';

		$new[self::OPTION_COMPLETED_EMAIL_ENABLED] = isset($input[self::OPTION_COMPLETED_EMAIL_ENABLED]) && trim($input[self::OPTION_COMPLETED_EMAIL_ENABLED]) === '1';

		$new[self::OPTION_DISPLAY_ATTACHMENT_NAME] = isset($input[self::OPTION_DISPLAY_ATTACHMENT_NAME]) && trim($input[self::OPTION_DISPLAY_ATTACHMENT_NAME]) === '1';

		$new[self::OPTION_ENABLE_LOGGING] = isset($input[self::OPTION_ENABLE_LOGGING]) && trim($input[self::OPTION_ENABLE_LOGGING]) === '1';

		return $new;
	}

	public static function get(string $option, mixed $default = false): mixed
	{
		$result = get_option('wcoa_general');
		if (empty($result))
		{
			return $default;
		}

		return get_option('wcoa_general')[$option] ?? $default;
	}
}