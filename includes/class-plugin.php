<?php
/**
 * Main Class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

use NewsletterGate\Abstracts\Integration;
use NewsletterGate\integrations\ConvertKit;
use NewsletterGate\integrations\MailChimp;
use NewsletterGate\integrations\Mailerlite;

/**
 * Plugin Class.
 */
class Plugin {

	/**
	 * Object for admin settings.
	 *
	 * @var Admin|null
	 */
	public $settings = null;

	/**
	 * Loaded integrations.
	 *
	 * @var array
	 */
	protected $integrations = array();

	/**
	 * Singletone Instance.
	 *
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * Get the static instance of this class.
	 *
	 * @return self|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 */
	public function __construct() {
		spl_autoload_register( array( $this, 'load_class' ) );

		$this->load_integrations();
		$this->settings = new Admin();
		new Shortcode();
		new AJAX();

		add_action( 'init', array( $this, 'run_installer' ) );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( NEWSLETTERGATE_URL );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( NEWSLETTERGATE_PATH );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		/**
		 * Filter to adjust the base templates path.
		 */
		return apply_filters( 'newslettergate_template_path', 'newslettergate/' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Run the Installer if needed.
	 *
	 * @return void
	 */
	public function run_installer() {
		$installer = new Installer();
		$installer->check_versions();
	}

	/**
	 * Load integrations.
	 *
	 * @return void
	 */
	public function load_integrations() {
		$this->integrations['mailchimp']  = new MailChimp();
		$this->integrations['convertkit'] = new ConvertKit();
		$this->integrations['mailerlite'] = new Mailerlite();
	}

	/**
	 * Get an integration by key.
	 *
	 * @param string $key Integration Key (ID).
	 *
	 * @return mixed|null|Integration
	 */
	public function get_integration( $key ) {
		return ! empty( $this->integrations[ $key ] ) ? $this->integrations[ $key ] : null;
	}

	/**
	 * Load a class under the NewsletterGate namespace.
	 *
	 * @param string $class Namespaced class name.
	 *
	 * @return void
	 */
	public function load_class( $class ) {
		$parts = explode( '\\', $class );

		if ( 'NewsletterGate' !== $parts[0] ) {
			return;
		}

		unset( $parts[0] );
		$parts      = array_map( 'strtolower', $parts );
		$class_name = str_replace( '_', '-', array_pop( $parts ) );
		$path       = ( ! empty( $parts ) ? implode( '/', $parts ) . '/' : '' ) . 'class-' . $class_name . '.php';
		include_once $path;
	}
}
