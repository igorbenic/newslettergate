<?php

namespace NewsletterGate;

use NewsletterGate\Abstracts\Integration;
use NewsletterGate\integrations\ConvertKit;
use NewsletterGate\integrations\MailChimp;
use NewsletterGate\integrations\Mailerlite;

class Plugin {

    public $settings = null;

    protected $integrations = [];

    protected static $instance = null;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

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

    public function run_installer() {
        $installer = new Installer();
        $installer->check_versions();
    }

    public function load_integrations() {
        $this->integrations['mailchimp'] = new MailChimp();
        $this->integrations['convertkit'] = new ConvertKit();
        $this->integrations['mailerlite'] = new Mailerlite();
    }

    /**
     * @param $key
     * @return mixed|null|Integration
     */
    public function get_integration( $key ) {
        return ! empty( $this->integrations[ $key ] ) ? $this->integrations[ $key ] : null;
    }

    public function load_class( $class ) {
        $parts = explode( '\\', $class );

        if ( 'NewsletterGate' !== $parts[0] ) {
            return;
        }

        unset( $parts[0] );
        $parts      = array_map( 'strtolower', $parts );
        $class_name = str_replace( '_', '-', array_pop( $parts ) );
        $path       = ( ! empty( $parts ) ? implode( '/', $parts  ) . '/' : '' ) . 'class-' . $class_name . '.php';
        include_once $path;
    }
}