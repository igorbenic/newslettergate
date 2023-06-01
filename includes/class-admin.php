<?php
/**
 * Admin Class to handle everything on the admin side.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

use NewsletterGate\Abstracts\Settings;

/**
 * Admin class.
 */
class Admin extends Settings {

	/**
	 * Constructor method.
	 */
	public function __construct() {
		$this->id         = 'newslettergate';
		$this->menu_title = __( 'NewsletterGate', 'newslettergate' );
		$this->page_title = __( 'NewsletterGate', 'newslettergate' );
		$this->menu_icon  = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1ODEgNTgxIj4KICAgIDxwYXRoIGZpbGw9IiNhN2FhYWQiIGQ9Im0xMTIuNDIsMTE2LjJoMzg0LjkxYzM2LjMxLDAsNTQuNjEsMTcuMTQsNTQuNjEsNTJ2MjQ0LjZjMCwzNC41Ny0xOC4zLDUyLTU0LjYxLDUySDExMi40MmMtMzYuMzEsMC01NC42MS0xNy40My01NC42MS01MnYtMjQ0LjZjMC0zNC44NiwxOC4zLTUyLDU0LjYxLTUyWm0xOTIuMzEsMjQ5LjgzbDE5NS44LTE2MC42NWM2Ljk3LTUuODEsMTIuNDktMTkuMTcsMy43OC0zMS4wOC04LjQyLTExLjkxLTIzLjgyLTEyLjItMzMuOTktNC45NGwtMTY1LjU4LDExMi4xMy0xNjUuMjktMTEyLjEzYy0xMC4xNy03LjI2LTI1LjU2LTYuOTctMzMuOTksNC45NC04LjcxLDExLjkxLTMuMiwyNS4yNywzLjc4LDMxLjA4bDE5NS41MSwxNjAuNjVaIi8+CiAgICA8cGF0aCBzdHJva2U9IiMwMDAiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgZD0ibTM4NC44OCwyNzQuNWgtMTZ2LTQ4YzAtMzUuMi0yOC44LTY0LTY0LTY0cy02NCwyOC44LTY0LDY0djQ4aC0xNmMtOCwwLTE2LDgtMTYsMTZ2MTEyYzAsOCw4LDE2LDE2LDE2aDE2MGM4LDAsMTYtOCwxNi0xNnYtMTEyYzAtOC04LTE2LTE2LTE2Wm0tNjQsMTEyaC0zMmw2LjQtMzUuMmMtOC0zLjItMTQuNC0xMi44LTE0LjQtMjAuOCwwLTEyLjgsMTEuMi0yNCwyNC0yNHMyNCwxMS4yLDI0LDI0YzAsOS42LTQuOCwxNy42LTE0LjQsMjAuOGw2LjQsMzUuMlptMTYtMTEyaC02NHYtNDhjMC0xNy42LDE0LjQtMzIsMzItMzJzMzIsMTQuNCwzMiwzMnY0OFoiLz4KPC9zdmc+';

		$this->set_fields();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		parent::__construct();
	}

	/**
	 * Enqueue scripts/styles in admin.
	 *
	 * @param string $hook Page hook.
	 *
	 * @return void
	 */
	public function enqueue( $hook ) {

		if ( 'toplevel_page_newslettergate_options' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'newslettergate-admin',
			NEWSLETTERGATE_URL . 'build/admin.js',
			array(
				'jquery',
				'wp-color-picker',
				'wp-util',
				'jquery-ui-sortable',
			),
			filemtime( NEWSLETTERGATE_PATH . 'build/admin.js' ),
			true
		);
		wp_enqueue_style(
			'newslettergate-admin',
			NEWSLETTERGATE_URL . 'build/admin.css',
			array(),
			filemtime( NEWSLETTERGATE_PATH . 'build/admin.css' ),
		);
	}

	/**
	 * Set settings fields.
	 *
	 * @return void
	 */
	protected function set_fields() {
		$this->fields = array(
			array(
				'title'       => __( 'Form', 'newslettergate' ),
				'type'        => 'section',
				'name'        => 'form_section',
				'description' => __( 'Settings for the form that shows to non-subscribers. Has an email to enter and check if they\'re on the list', 'newslettergate' ),
			),
			array(
				'title'       => __( 'Heading', 'newslettergate' ),
				'type'        => 'text',
				'name'        => 'heading',
				'default'     => __( 'Unlock by Subscribing', 'newslettergate' ),
				'description' => __( 'Heading to be shown on the newsletter form.', 'newslettergate' ),
				'section'     => 'form_section',
			),
			array(
				'title'       => __( 'Text', 'newslettergate' ),
				'type'        => 'text',
				'name'        => 'text',
				'default'     => __( 'Already Subscribed? Enter email to unlock', 'newslettergate' ),
				'description' => __( 'Text to be shown on the newsletter form. If heading is entered, it will be shown below heading', 'newslettergate' ),
				'section'     => 'form_section',
			),
			array(
				'title'       => __( 'Button', 'newslettergate' ),
				'type'        => 'text',
				'name'        => 'button',
				'default'     => __( 'Unlock', 'newslettergate' ),
				'description' => __( 'Button Text. Defaults to Unlock', 'newslettergate' ),
				'section'     => 'form_section',
			),
			array(
				'title'       => __( 'Form - Subscribe', 'newslettergate' ),
				'type'        => 'section',
				'name'        => 'form_subscribe_section',
				'description' => __( 'Settings for the form that subscribes users. It shows if enabled and an email is not subscribed to the list.', 'newslettergate' ),
			),
			array(
				'title'       => __( 'Enable', 'newslettergate' ),
				'type'        => 'checkbox',
				'name'        => 'enable_subscribe',
				'default'     => 0,
				'description' => __( 'If enabled, users will be able to subscribe.', 'newslettergate' ),
				'section'     => 'form_subscribe_section',
			),
			array(
				'title'       => __( 'Heading', 'newslettergate' ),
				'type'        => 'text',
				'name'        => 'heading_subscribe',
				'default'     => __( 'Not subscribed yet', 'newslettergate' ),
				'description' => __( 'Heading to be shown on the newsletter form.', 'newslettergate' ),
				'section'     => 'form_subscribe_section',
			),
			array(
				'title'       => __( 'Text', 'newslettergate' ),
				'type'        => 'text',
				'name'        => 'text_subscribe',
				'default'     => __( 'Subscribe to unlock this content', 'newslettergate' ),
				'description' => __( 'Text to be shown on the newsletter form. If heading is entered, it will be shown below heading', 'newslettergate' ),
				'section'     => 'form_subscribe_section',
			),
			array(
				'title'       => __( 'Button', 'newslettergate' ),
				'type'        => 'text',
				'name'        => 'button_subscribe',
				'default'     => __( 'Subscribe to Unlock', 'newslettergate' ),
				'description' => __( 'Button Text. Defaults to Subscribe to Unlock', 'newslettergate' ),
				'section'     => 'form_subscribe_section',
			),
			array(
				'title' => __( 'Form Style', 'newslettergate' ),
				'type'  => 'section',
				'name'  => 'form_style_section',
			),
			array(
				'title'       => __( 'Background Color', 'newslettergate' ),
				'type'        => 'color',
				'name'        => 'bg_color',
				'default'     => '#000',
				'description' => __( 'Form background color', 'newslettergate' ),
				'section'     => 'form_style_section',
			),
			array(
				'title'       => __( 'Heading Color', 'newslettergate' ),
				'type'        => 'color',
				'name'        => 'heading_color',
				'default'     => '#fff',
				'description' => __( 'Form heading color', 'newslettergate' ),
				'section'     => 'form_style_section',
			),
			array(
				'title'       => __( 'Text Color', 'newslettergate' ),
				'type'        => 'color',
				'name'        => 'text_color',
				'default'     => '#fff',
				'description' => __( 'Form text color', 'newslettergate' ),
				'section'     => 'form_style_section',
			),
			array(
				'title'       => __( 'Button Background', 'newslettergate' ),
				'type'        => 'color',
				'name'        => 'button_bg_color',
				'default'     => '#fff',
				'description' => __( 'Form button background color', 'newslettergate' ),
				'section'     => 'form_style_section',
			),
			array(
				'title'       => __( 'Button Color', 'newslettergate' ),
				'type'        => 'color',
				'name'        => 'button_text_color',
				'default'     => '#000',
				'description' => __( 'Form button text color', 'newslettergate' ),
				'section'     => 'form_style_section',
			),
		);
	}
}
