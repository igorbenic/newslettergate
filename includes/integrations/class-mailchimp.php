<?php

namespace NewsletterGate\integrations;

use NewsletterGate\Abstracts\API;

class MailChimp extends API {

    protected $id = 'mailchimp';

    protected $api_url = 'https://<dc>.api.mailchimp.com/3.0/';

    public function __construct() {
        add_filter( 'newslettergate_get_settings_fields', [ $this, 'add_fields' ] );
    }

    public function get_api_url() {
        $api_url = 'https://<dc>.api.mailchimp.com/3.0/';
        $api_key = $this->get_api_key();

        if ( ! $api_key ) {
            return false;
        }

        $api_parts = explode( '-', $api_key );

        return str_replace( '<dc>', $api_parts[1], $api_url );
    }

    public function add_fields( $fields ) {
        $fields[] = [
            'title' => __( 'MailChimp', 'newslettergate' ),
            'type'  => 'section',
            'name'  => 'mailchimp_section',
        ];

        $fields[] = [
            'title' => __( 'Enable', 'newslettergate' ),
            'type'  => 'checkbox',
            'name'  => 'mailchimp_enabled',
            'description' => __( 'Enable to be used for subscribing people for restricting content. If disabled, all content gated by MailChimp will be shown.', 'newslettergate' ),
            'section' => 'mailchimp_section',
        ];

        $fields[] = [
            'title' => __( 'API Key', 'newslettergate' ),
            'type'  => 'text',
            'name'  => 'mailchimp_api_key',
            'section' => 'mailchimp_section'
        ];

        $fields[] = [
            'title' => __( 'Lists', 'newslettergate' ),
            'type'  => 'lists',
            'name'  => 'mailchimp_lists',
            'section' => 'mailchimp_section',
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
                        <code>[newslettergate provider=mailchimp list=<?php echo esc_html( $list_id ); ?>]Content To Gate[/newslettergate]</code>
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
            'Authorization' => 'Basic ' . base64_encode( 'newslettergate:' . $this->get_api_key() ),
        ];
    }

    public function get_lists() {
        $lists = $this->get( 'lists');

        if ( is_wp_error( $lists ) ) {
            return $lists;
        }

        $body  = wp_remote_retrieve_body( $lists );

        return wp_list_pluck( json_decode( $body, true )['lists'], 'name', 'id' );
    }

    protected function prepare_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( wp_remote_retrieve_response_code( $response ) < 300 ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['detail'] ) ) {
            return new \WP_Error( wp_remote_retrieve_response_code( $response ), $body['detail'] );
        }

        if ( ! empty( $body['title'] ) ) {
            return new \WP_Error( wp_remote_retrieve_response_code( $response ), $body['title'] );
        }

        return new \WP_Error( wp_remote_retrieve_response_code( $response ), __( 'Something went wrong.', 'newslettergate' ));;
    }

    public function is_subscribed_on_provider( $email, $list_id = null ) {
        $member = $this->get('lists/' . $list_id . '/members/' . md5( strtolower( $email ) ) );

        if ( is_wp_error( $member ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $member ), true );

        return isset( $body['status'] ) && absint( $body['status'] ) === 404 ? false : true;
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
            'email_address' => $email,
            'status'        => 'subscribed',
            'status_if_new' => 'subscribed'
        );

        $post = $this->put( 'lists/' . $list_id . '/members/' . md5( strtolower( $email ) ), $body );

        if ( ! is_wp_error( $post ) ) {
            return true;
        }

        return $post;
    }
}