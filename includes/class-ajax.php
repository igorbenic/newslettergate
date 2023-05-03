<?php

namespace NewsletterGate;

use NewsletterGate\Abstracts\API;
use NewsletterGate\Abstracts\Integration;

class AJAX {


    public function __construct() {
        $actions = [
            'check'     => true,
            'subscribe' => true
        ];

        foreach ( $actions as $action => $nopriv ) {
            add_action( 'wp_ajax_newslettergate_' . $action, [ $this, $action ] );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_newslettergate_' . $action, [ $this, $action ] );
            }
        }

    }

    public function check() {
        check_ajax_referer( 'newslettergate', 'nonce' );

        $posted_data = $_POST;
        $atts        = [];
        parse_str( $posted_data['data'], $atts );

        /** @var Integration $integration */
        $integration = newslettergate()->get_integration( $atts['ng_integration'] );

        if ( ! $integration ) {
            wp_send_json_error();
            wp_die();
        }

        if ( ! $integration->is_enabled() ) {
            wp_send_json_error();
            wp_die();
        }

        $subscribed = $integration->is_subscribed( $atts['ng_email'], $atts['ng_list'] );

        $data = [];

        if ( $subscribed ) {
            $subscriber = new Subscriber();
            $subscriber = $subscriber->maybe_find_subscriber( $atts['ng_email'], $atts['ng_integration'], $atts['ng_list'] );
            $subscriber->save();
            $data['reload'] = true;
            wp_send_json_success( $data );
            wp_die();
        }

        $subscribe_enabled = absint( newslettergate()->settings->get_option( 'enable_subscribe', 0 ) );

        if ( ! $subscribe_enabled ) {
            $heading = newslettergate()->settings->get_option( 'heading_subscribe', '' );
            $text    = newslettergate()->settings->get_option( 'text_subscribe', '' );
            $button  = newslettergate()->settings->get_option( 'button_subscribe', __( 'Subscribe to Unlock', 'newslettergate' ) );

            $templates = new Templates();
            ob_start();

            $templates->get_template( 'forms/default.php', [
                'heading'     => $heading,
                'text'        => $text,
                'button'      => $button,
                'integration' => $atts['ng_integration'],
                'list_id'     => $atts['ng_list'],
                'email'       => $atts['ng_email'],
                'errors'      => [ __( 'Your email is not subscribed to our email list. Please contact us to learn more.', 'newslettergate' ) ]
            ]);

            $html = ob_get_clean();
            $data = [ 'html' => $html ];
            wp_send_json_success( $data );
            wp_die();
        }

        $heading = newslettergate()->settings->get_option( 'heading_subscribe', '' );
        $text    = newslettergate()->settings->get_option( 'text_subscribe', '' );
        $button  = newslettergate()->settings->get_option( 'button_subscribe', __( 'Subscribe to Unlock', 'newslettergate' ) );

        $templates = new Templates();
        ob_start();

        $templates->get_template( 'forms/subscribe.php', [
            'heading'     => $heading,
            'text'        => $text,
            'button'      => $button,
            'integration' => $atts['ng_integration'],
            'list_id'     => $atts['ng_list'],
            'email'       => $atts['ng_email']
        ]);

        $html = ob_get_clean();

        $data = [
            'html' => $html
        ];
        wp_send_json_success( $data );
        wp_die();
    }

    public function subscribe() {
        check_ajax_referer( 'newslettergate', 'nonce' );

        $posted_data = $_POST;
        $atts        = [];
        parse_str( $posted_data['data'], $atts );

        $subscribe_enabled = absint( newslettergate()->settings->get_option( 'enable_subscribe', 0 ) );

        if ( ! $subscribe_enabled ) {
            $heading = newslettergate()->settings->get_option( 'heading_subscribe', '' );
            $text    = newslettergate()->settings->get_option( 'text_subscribe', '' );
            $button  = newslettergate()->settings->get_option( 'button_subscribe', __( 'Subscribe to Unlock', 'newslettergate' ) );

            $templates = new Templates();
            ob_start();

            $templates->get_template( 'forms/default.php', [
                'heading'     => $heading,
                'text'        => $text,
                'button'      => $button,
                'integration' => $atts['ng_integration'],
                'list_id'     => $atts['ng_list'],
                'email'       => $atts['ng_email'],
                'errors'      => [ __( 'Your email is not subscribed to our email list. Please contact us to learn more.', 'newslettergate' ) ]
            ]);

            $html = ob_get_clean();
            $data = [ 'html' => $html ];
            wp_send_json_success( $data );
            wp_die();
        }

        /** @var API $integration */
        $integration = newslettergate()->get_integration( $atts['ng_integration'] );

        if ( ! $integration ) {
            wp_send_json_error();
            wp_die();
        }

        if ( ! $integration->is_enabled() ) {
            wp_send_json_error();
            wp_die();
        }

        $subscribed = $integration->subscribe( $atts['ng_email'], $atts['ng_list'] );

        if ( $subscribed && ! is_wp_error( $subscribed ) ) {
            $subscriber = new Subscriber();
            $subscriber = $subscriber->maybe_find_subscriber( $atts['ng_email'], $atts['ng_integration'], $atts['ng_list'] );
            $subscriber->save();
            $data['reload'] = true;
            wp_send_json_success( $data );
            wp_die();
        }

        $error = __( 'We could not subscribe you. Please contact us', 'newslettergate' );

        if ( is_wp_error( $subscribed ) ) {
            $error = $subscribed->get_error_message();
        }

        $heading = newslettergate()->settings->get_option( 'heading_subscribe', '' );
        $text    = newslettergate()->settings->get_option( 'text_subscribe', '' );
        $button  = newslettergate()->settings->get_option( 'button_subscribe', __( 'Subscribe to Unlock', 'newslettergate' ) );

        $templates = new Templates();
        ob_start();

        $templates->get_template( 'forms/subscribe.php', [
            'heading'     => $heading,
            'text'        => $text,
            'button'      => $button,
            'integration' => $atts['ng_integration'],
            'list_id'     => $atts['ng_list'],
            'email'       => $atts['ng_email'],
            'errors'      => [ $error ]
        ]);

        $html = ob_get_clean();

        $data = [
            'html' => $html
        ];
        wp_send_json_success( $data );
        wp_die();
    }
}