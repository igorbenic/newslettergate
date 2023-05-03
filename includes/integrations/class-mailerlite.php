<?php

namespace NewsletterGate\integrations;

use NewsletterGate\Abstracts\API;

class Mailerlite extends API {

    protected $id = 'mailerlite';

    protected $api_url = 'https://api.mailerlite.com/api/v2/';

    public function __construct() {
        add_filter( 'newslettergate_get_settings_fields', [ $this, 'add_fields' ] );
    }

    public function add_fields( $fields ) {
        $fields[] = [
            'title' => __( 'Mailerlite', 'newslettergate' ),
            'type'  => 'section',
            'name'  => 'mailerlite_section',
        ];

        $fields[] = [
            'title' => __( 'Enable', 'newslettergate' ),
            'type'  => 'checkbox',
            'name'  => 'mailerlite_enabled',
            'description' => __( 'Enable to be used for subscribing people for restricting content. If disabled, all content gated by Mailerlite will be shown.', 'newslettergate' ),
            'section' => 'mailerlite_section',
        ];

        $fields[] = [
            'title' => __( 'API Key', 'newslettergate' ),
            'type'  => 'text',
            'name'  => 'mailerlite_api_key',
            'section' => 'mailerlite_section'
        ];

        $fields[] = [
            'title' => __( 'Lists', 'newslettergate' ),
            'type'  => 'lists',
            'name'  => 'mailerlite_lists',
            'section' => 'mailerlite_section',
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
                        <code>[newslettergate provider=mailerlite list=<?php echo esc_html( $list_id ); ?>]Content To Gate[/newslettergate]</code>
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
            'Content-Type' => 'application/json',
            'X-MailerLite-ApiKey' => $this->get_api_key()
        ];
    }

    public function get_lists() {
        $lists = $this->get( 'groups' );

        if ( is_wp_error( $lists ) ) {
            return $lists;
        }

        $body  = wp_remote_retrieve_body( $lists );

        return wp_list_pluck( json_decode( $body, true ), 'name', 'id' );
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
            return new \WP_Error( wp_remote_retrieve_response_code( $response ), $body['error']['message'] );
        }

        return new \WP_Error( wp_remote_retrieve_response_code( $response ), __( 'Something went wrong.', 'newslettergate' ));;
    }

    public function is_subscribed_on_provider( $email, $list_id = null ) {
        $resp = $this->get('subscribers/' . $email . '/groups' );
        //$resp = $this->get('subscribers/' . $email  );
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( ! $body ) {
            return false;
        }

        if ( empty( $body ) ) {
            return false;
        }

        $subscriber_groups = wp_list_pluck( $body, 'name', 'id' );

        if ( ! isset( $subscriber_groups[ $list_id ] ) ) {
            return false;
        }

        return true;
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
            'email'          => $email,
            'resubscribe'    => true,
            'autoresponders' => true,
            'type'           => 'active'
        );

        $post = $this->post( 'groups/' . $list_id . '/subscribers', $body );

        if ( ! is_wp_error( $post ) ) {
            return true;
        }

        return $post;
    }
}