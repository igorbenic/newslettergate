<?php
/**
 * Plugin Name: NewsletterGate
 * Description: Grow your email list by restricting content only to your subscribers.
 * Author: Igor Benić
 * Author URI: https://ibenic.com
 * Version: 1.2.0
 * Textdomain: newslettergate
 */

if ( ! defined( 'ABSPATH' ) ) {
    return;
}

define( 'NEWSLETTERGATE_FILE', __FILE__ );
define( 'NEWSLETTERGATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'NEWSLETTERGATE_URL', plugin_dir_url( __FILE__ ) );
define( 'NEWSLETTERGATE_VERSION', '1.2.0' );

require_once 'includes/class-plugin.php';

function newslettergate() {
    return \NewsletterGate\Plugin::get_instance();
}

newslettergate();
