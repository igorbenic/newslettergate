<?php

namespace NewsletterGate;

class Subscriber {

    protected $id = null;

    protected $data = null;

    public function set_data( $data ) {
        $this->data = $data;
    }

    public function get_data() {
        return $this->data;
    }

    public function maybe_find_subscriber( $email, $provider, $list_id ) {
        $db   = new DB();
        $this->set_data([
            'email'    => $email,
            'provider' => $provider,
            'list_id'  => $list_id
        ]);
        $subs = $db->get_by_columns([
            'email'    => $email,
            'provider' => $provider,
            'list_id'  => $list_id
        ]);

        if ( $subs ) {
            foreach ( $subs as $sub ) {
                $this->id = $sub['id'];
                $this->data = $sub;
                return $this;
            }
        }

        return $this;
    }

    public function save() {
        if ( $this->id ) {
            $this->update();
        } else {
            $this->add();
        }

        $this->create_cookie();
    }

    /**
     * Create Cookie for a registered user
     *
     * @return void
     */
    public function create_cookie() {

        if ( headers_sent() ) {
            return;
        }

        $provider = $this->data['provider'];
        $integration = newslettergate()->get_integration( $provider );

        if ( ! $integration ) {
            return;
        }

        $cookies = $integration->get_cookies_for_integration();

        $db   = new DB();
        $subs = $db->get_by_columns([
            'email'    => $this->data['email'],
            'provider' => $this->data['provider'],
            'list_id'  => $this->data['list_id']
        ]);

        if ( $cookies ) {
            foreach ( $cookies as $cookie_name => $cookie_value ) {
                if ( $subs ) {
                    foreach ( $subs as $subscriber ) {
                        if ( $cookie_value === $subscriber['ref_id'] ) {
                            // Exists.
                            return;
                        }
                    }
                }
            }
        }


        $new_cookie_order = count( $cookies ) + 1;

        // Find cookies that exist
        // Find if one has ref_id connected to this email, provider, listid
        // If not, create a new one.
        setcookie( 'ngate_' . $this->data['provider'] . '_' . $new_cookie_order, $this->data['ref_id'], time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
    }

    public function update() {
        global $wpdb;

        $expires_at = date( 'Y-m-d H:i:s', time() + MONTH_IN_SECONDS );
        $wpdb->update(
            $wpdb->prefix . 'newslettergate_subscribers',
            [
                'expires_at' => $expires_at
            ],
            [
                'id' => $this->id
            ]
        );
    }

    public function add() {
        global $wpdb;

        $this->data['ref_id'] = md5( $this->data['email'] . $this->data['list_id'] . $this->data['provider'] );

        $wpdb->insert(
            $wpdb->prefix . 'newslettergate_subscribers',
            [
                'ref_id'     => $this->data['ref_id'],
                'user_id'    => get_current_user_id(),
                'list_id'    => $this->data['list_id'],
                'email'      => $this->data['email'],
                'provider'   => $this->data['provider'],
                'date'       => current_time( 'mysql' ),
                'expires_at' => date( 'Y-m-d H:i:s', time() + MONTH_IN_SECONDS )
            ]
        );
    }
}