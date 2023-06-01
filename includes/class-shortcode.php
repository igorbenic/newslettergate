<?php
/**
 * Shortcode class to manage everything around shortcodes.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

/**
 * Shortcode class.
 */
class Shortcode {

	/**
	 * Constructor method.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @return void
	 */
	public function enqueue() {

		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		if ( ! has_shortcode( $post->post_content, 'newslettergate' ) ) {
			return;
		}

		wp_enqueue_style(
			'newslettergate',
			trailingslashit( NEWSLETTERGATE_URL ) . 'build/index.css',
			null,
			filemtime( trailingslashit( NEWSLETTERGATE_PATH ) . 'build/index.css' )
		);
		wp_enqueue_script(
			'newslettergate',
			trailingslashit( NEWSLETTERGATE_URL ) . 'build/index.js',
			array( 'jquery' ),
			filemtime( trailingslashit( NEWSLETTERGATE_PATH ) . 'build/index.js' ),
			true
		);
		wp_localize_script(
			'newslettergate',
			'newslettergate',
			array(
				'nonce' => wp_create_nonce( 'newslettergate' ),
				'ajax'  => admin_url( 'admin-ajax.php' ),
			)
		);

		$variables     = array();
		$bg_color      = newslettergate()->settings->get_option( 'bg_color' );
		$heading_color = newslettergate()->settings->get_option( 'color' );
		$text_color    = newslettergate()->settings->get_option( 'text_color' );
		$btn_bg        = newslettergate()->settings->get_option( 'button_bg_color' );
		$btn_color     = newslettergate()->settings->get_option( 'button_text_color' );
		if ( $bg_color ) {
			$variables['--newslettergate-background'] = $bg_color;
		}
		if ( $heading_color ) {
			$variables['--newslettergate-heading'] = $heading_color;
		}
		if ( $text_color ) {
			$variables['--newslettergate-text'] = $text_color;
		}
		if ( $btn_bg ) {
			$variables['--newslettergate-button-bg'] = $btn_bg;
		}
		if ( $btn_color ) {
			$variables['--newslettergate-button-text'] = $btn_color;
		}

		$css = '';
		foreach ( $variables as $var => $value ) {
			$css .= "\n" . $var . ': ' . $value . ';';
		}

		if ( $variables ) {
			wp_add_inline_style( 'newslettergate', '.newslettergate { ' . $css . ' }' );
		}
	}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'newslettergate', array( $this, 'shortcode' ) );
	}

	/**
	 * Render shortcode.
	 *
	 * @param array  $atts Shortcode Attributes.
	 * @param string $content Wrapped content.
	 *
	 * @return false|mixed|string|null
	 */
	public function shortcode( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'provider' => '',
				'list'     => '',
			),
			$atts
		);

		$integration = newslettergate()->get_integration( sanitize_text_field( $atts['provider'] ) );

		if ( ! $integration ) {
			return $content;
		}

		if ( ! $integration->is_enabled() ) {
			return $content;
		}

		$subscribed = $integration->is_subscribed();

		if ( $subscribed ) {
			return $content;
		}

		$heading = newslettergate()->settings->get_option( 'heading', '' );
		$text    = newslettergate()->settings->get_option( 'text', '' );
		$button  = newslettergate()->settings->get_option( 'button', __( 'Unlock', 'newslettergate' ) );

		$templates = new Templates();
		ob_start();

		$templates->get_template(
			'forms/default.php',
			array(
				'heading'     => $heading,
				'text'        => $text,
				'button'      => $button,
				'integration' => sanitize_text_field( $atts['provider'] ),
				'list_id'     => sanitize_text_field( $atts['list'] ),
			)
		);

		return ob_get_clean();
	}
}
