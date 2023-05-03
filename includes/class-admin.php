<?php

namespace NewsletterGate;

use NewsletterGate\Abstracts\Settings;

class Admin extends Settings {

    /**
     * Constructor method.
     */
    public function __construct() {
        $this->id = 'newslettergate';
        $this->menu_title = __( 'NewsletterGate', 'newslettergate' );
        $this->page_title = __( 'NewsletterGate', 'newslettergate' );
        $this->menu_icon  = 'dashicons-email';
        $this->menu_icon  = 'data:image/svg+xml;base64,' . base64_encode( file_get_contents(trailingslashit( NEWSLETTERGATE_PATH ) . 'src/images/icon.svg' ) );

        $this->set_fields();
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        parent::__construct();
    }

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
            )
        );
        wp_enqueue_style(
            'newslettergate-admin',
            NEWSLETTERGATE_URL . 'build/admin.css',
        );
    }

    protected function set_fields() {
        $this->fields = [
            [
                'title' => __( 'Form', 'newslettergate' ),
                'type'  => 'section',
                'name'  => 'form_section',
                'description' => __( 'Settings for the form that shows to non-subscribers. Has an email to enter and check if they\'re on the list', 'newslettergate' ),
            ],
            [
                'title' => __( 'Heading', 'newslettergate' ),
                'type'  => 'text',
                'name'  => 'heading',
                'default' => __( 'Unlock by Subscribing', 'newslettergate' ),
                'description' => __( 'Heading to be shown on the newsletter form.', 'newslettergate' ),
                'section' => 'form_section'
            ],
            [
                'title' => __( 'Text', 'newslettergate' ),
                'type'  => 'text',
                'name'  => 'text',
                'default' => __( 'Already Subscribed? Enter email to unlock', 'newslettergate' ),
                'description' => __( 'Text to be shown on the newsletter form. If heading is entered, it will be shown below heading', 'newslettergate' ),
                'section' => 'form_section'
            ],
            [
                'title' => __( 'Button', 'newslettergate' ),
                'type'  => 'text',
                'name'  => 'button',
                'default' => __( 'Unlock', 'newslettergate' ),
                'description' => __( 'Button Text. Defaults to Unlock', 'newslettergate' ),
                'section' => 'form_section'
            ],
            [
                'title' => __( 'Form - Subscribe', 'newslettergate' ),
                'type'  => 'section',
                'name'  => 'form_subscribe_section',
                'description' => __( 'Settings for the form that subscribes users. It shows if enabled and an email is not subscribed to the list.', 'newslettergate' ),
            ],
            [
                'title' => __( 'Enable', 'newslettergate' ),
                'type'  => 'checkbox',
                'name'  => 'enable_subscribe',
                'default' => 0,
                'description' => __( 'If enabled, users will be able to subscribe.', 'newslettergate' ),
                'section' => 'form_subscribe_section'
            ],
            [
                'title' => __( 'Heading', 'newslettergate' ),
                'type'  => 'text',
                'name'  => 'heading_subscribe',
                'default' => __( 'Not subscribed yet', 'newslettergate' ),
                'description' => __( 'Heading to be shown on the newsletter form.', 'newslettergate' ),
                'section' => 'form_subscribe_section'
            ],
            [
                'title' => __( 'Text', 'newslettergate' ),
                'type'  => 'text',
                'name'  => 'text_subscribe',
                'default' => __( 'Subscribe to unlock this content', 'newslettergate' ),
                'description' => __( 'Text to be shown on the newsletter form. If heading is entered, it will be shown below heading', 'newslettergate' ),
                'section' => 'form_subscribe_section'
            ],
            [
                'title' => __( 'Button', 'newslettergate' ),
                'type'  => 'text',
                'name'  => 'button_subscribe',
                'default' => __( 'Subscribe to Unlock', 'newslettergate' ),
                'description' => __( 'Button Text. Defaults to Subscribe to Unlock', 'newslettergate' ),
                'section' => 'form_subscribe_section'
            ],
            [
                'title' => __( 'Form Style', 'newslettergate' ),
                'type'  => 'section',
                'name'  => 'form_style_section',
            ],
            [
                'title' => __( 'Background Color', 'newslettergate' ),
                'type'  => 'color',
                'name'  => 'bg_color',
                'default' => '#000',
                'description' => __( 'Form background color', 'newslettergate' ),
                'section' => 'form_style_section'
            ],
            [
                'title' => __( 'Heading Color', 'newslettergate' ),
                'type'  => 'color',
                'name'  => 'heading_color',
                'default' => '#fff',
                'description' => __( 'Form heading color', 'newslettergate' ),
                'section' => 'form_style_section'
            ],
            [
                'title' => __( 'Text Color', 'newslettergate' ),
                'type'  => 'color',
                'name'  => 'text_color',
                'default' => '#fff',
                'description' => __( 'Form text color', 'newslettergate' ),
                'section' => 'form_style_section'
            ],
            [
                'title' => __( 'Button Background', 'newslettergate' ),
                'type'  => 'color',
                'name'  => 'button_bg_color',
                'default' => '#fff',
                'description' => __( 'Form button background color', 'newslettergate' ),
                'section' => 'form_style_section'
            ],
            [
                'title' => __( 'Button Color', 'newslettergate' ),
                'type'  => 'color',
                'name'  => 'button_text_color',
                'default' => '#000',
                'description' => __( 'Form button text color', 'newslettergate' ),
                'section' => 'form_style_section'
            ]
        ];
    }

}