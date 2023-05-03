<?php

namespace NewsletterGate\integrations;

use NewsletterGate\Abstracts\API;

class ConvertKit extends API {

    protected $id = 'convertkit';

    protected $api_url = 'https://api.convertkit.com/v3/';

    public function __construct() {
        add_filter( 'newslettergate_get_settings_fields', [ $this, 'add_fields' ] );
    }

    public function add_fields( $fields ) {
        $fields[] = [
            'title' => __( 'ConvertKit', 'newslettergate' ),
            'type'  => 'section',
            'name'  => 'convertkit_section',
        ];

        $fields[] = [
            'title' => __( 'Enable', 'newslettergate' ),
            'type'  => 'checkbox',
            'name'  => 'convertkit_enabled',
            'description' => __( 'Enable to be used for subscribing people for restricting content. If disabled, all content gated by ConvertKit will be shown.', 'newslettergate' ),
            'section' => 'convertkit_section',
        ];

        $fields[] = [
            'title' => __( 'API Secret Key', 'newslettergate' ),
            'type'  => 'text',
            'name'  => 'convertkit_api_key',
            'section' => 'convertkit_section'
        ];

        $fields[] = [
            'title' => __( 'Lists', 'newslettergate' ),
            'type'  => 'lists',
            'name'  => 'convertkit_lists',
            'section' => 'convertkit_section',
            'render' => [ $this, 'render_lists' ]
        ];

        return $fields;
    }

    public function render_lists( $args ) {
        $lists = $this->get_lists();
        if ( is_wp_error( $lists ) ) {
            ?>
            <p style="color:red;"><?php echo esc_html( $lists->get_error_message() ); ?></p>
            <?php
            return;
        }
        ?>
        <table class="wp-list-table widefat striped posts">
            <?php
            foreach ( $lists as $list_id => $list_name ) {
                ?>
                <tr>
                    <td>
                        <?php echo esc_html( $list_name ); ?>
                    </td>
                    <td>
                        <code>[newslettergate provider=convertkit list=<?php echo esc_html( $list_id ); ?>]Content To Gate[/newslettergate]</code>
                    </td>
                </tr>
                <?php
            }
            ?>

        </table>
        <?php
    }

    protected function get_default_headers() {
        return [
            'Content-Type' => 'application/json; charset=utf-8',
        ];
    }

    public function get_lists() {
        $lists = $this->get('forms?api_secret=' . $this->get_api_key() );

        if ( is_wp_error( $lists ) ) {
            return $lists;
        }

        $body  = wp_remote_retrieve_body( $lists );

        return wp_list_pluck( json_decode( $body, true )['forms'], 'name', 'id' );
    }

    protected function prepare_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( wp_remote_retrieve_response_code( $response ) < 300 ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['error'] ) ) {
            return new \WP_Error( wp_remote_retrieve_response_code( $response ), $body['error'] . ': ' . $body['message'] );
        }

        return new \WP_Error( wp_remote_retrieve_response_code( $response ), __( 'Something went wrong.', 'newslettergate' ));;
    }

    /**
     * Get Subscribers.
     *
     * @param string $form_id
     * @param int    $page
     * @return false|mixed
     */
    public function get_subscribers_for_form( $form_id, $page = 1 ) {
        $members = $this->get('forms/' . $form_id . '/subscriptions?subscriber_state=active&api_secret=' . $this->get_api_key() . '&page=' . $page );

        if ( is_wp_error( $members ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $members ), true );
    }

    public function find_subscriber_in_form( $email, $form_id ) {

        $response = $this->get_subscribers_for_form( $form_id );

        if ( empty( $response['subscriptions'] ) ) {
            return false;
        }

        $subs = wp_list_pluck( $response['subscriptions'], 'subscriber' );
        foreach ( $subs as $sub ) {
            if ( $sub['email_address'] === $email ) {
                return true;
            }
        }

        $total_pages = $response['total_pages'];

        for( $page = 2; $page <= $total_pages; $page++ ) {
            $response = $this->get_subscribers_for_form( $form_id, $page );
            if ( empty( $response['subscriptions'] ) ) {
                return false;
            }

            $subs = wp_list_pluck( $response['subscriptions'], 'subscriber' );
            foreach ( $subs as $sub ) {
                if ( $sub['email_address'] === $email ) {
                    return true;
                }
            }
        }

        return false;

    }

    public function is_subscribed_on_provider( $email, $list_id = null ) {
        return $this->find_subscriber_in_form( $email, $list_id );
    }

    protected function prepare_body( $body ) {
        return json_encode( $body );
    }

    /**
     * Subscribe an email.
     *
     * @param string $email Email.
     * @param mixed  $list_id List ID.
     *
     * @return true|\WP_Error
     */
    public function subscribe( $email, $list_id ) {
        $body = array(
            'email'      => $email,
            'api_secret' => $this->get_api_key(),
        );

        $post = $this->post( 'forms/' . $list_id . '/subscribe', $body );

        if ( ! is_wp_error( $post ) ) {
            return true;
        }

        return $post;
    }
}