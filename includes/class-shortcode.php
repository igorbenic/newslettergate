<?php

namespace NewsletterGate;

use NewsletterGate\integrations\MailChimp;

class Shortcode {

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    }

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
            [ 'jquery' ],
            filemtime( trailingslashit( NEWSLETTERGATE_PATH ) . 'build/index.js' ),
            true
        );
        wp_localize_script(
            'newslettergate',
            'newslettergate',
            [
                'nonce' => wp_create_nonce( 'newslettergate' ),
                'ajax'  => admin_url( 'admin-ajax.php' )
            ]
        );

        $variables     = [];
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

    public function register() {
        add_shortcode( 'newslettergate', array( $this, 'shortcode' ) );
    }

    public function shortcode( $atts, $content = null ) {

        $atts = shortcode_atts( [
            'provider' => '',
            'list'  => ''
        ], $atts );

        $integration = newslettergate()->get_integration( $atts['provider'] );

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

        $templates->get_template( 'forms/default.php', [
            'heading'     => $heading,
            'text'        => $text,
            'button'      => $button,
            'integration' => $atts['provider'],
            'list_id'     => $atts['list']
        ]);

        return ob_get_clean();
    }
}