<?php

namespace NewsletterGate\Abstracts;

use NewsletterGate\DB;

abstract class Integration {

    protected $id = null;

    public function get_id() {
        return $this->id;
    }

    public function is_enabled() {
        return absint( $this->get_option( 'enabled', '0' ) ) === 1;
    }

    public function get_option( $key, $default = null ) {
        $key = $this->id . '_' . $key;
        return newslettergate()->settings->get_option( $key, $default );
    }

    public function get_cookies_for_integration() {
        $found_cookies = [];
        $cookies       = $_COOKIE;
        $cookie_prefix = 'ngate_' . $this->id;
        foreach ( $cookies as $cookie_name => $cookie_value ) {
            $found = strpos( $cookie_name, $cookie_prefix );
            if ( false === $found || $found > 0 ) {
                continue;
            }

            $found_cookies[ $cookie_name ] = $cookie_value;
        }

        return $found_cookies;
    }

    public function is_subscribed_by_cookie( $list_id = null ) {
        $cookies = $this->get_cookies_for_integration();

        if ( ! $cookies ) {
            return false;
        }

        $db   = new DB();
        $data = $db->get_by_column( 'ref_id', $cookies );

        if ( ! $data || empty( $data ) ) {
            return false;
        }

        $now   = time();
        $valid = false;

        foreach ( $data as $row ) {
            if ( $row['expires_at'] && strtotime( $row['expires_at'] ) > $now ) {
                if ( null !== $list_id && $list_id !== $row['list_id'] ) {
                    // Maybe it's valid, but not for the provided list.
                    continue;
                }
                $valid = true;
                break;
            }
        }

        return $valid;
    }

    public function is_subscribed_by_email( $email, $list_id = null ) {
        $db   = new DB();
        $data = $db->get_by_column( 'email', $email );

        if ( ! $data ) {
            return false;
        }

        $now = time();
        foreach ( $data as $row ) {
            if ( $row['expires_at'] && strtotime( $row['expires_at'] ) > $now ) {
                if ( null !== $list_id && $list_id !== $row['list_id'] ) {
                    // Maybe it's valid, but not for the provided list.
                    continue;
                }
                $valid = true;
                break;
            }
        }

        return $valid;
    }

    /**
     * Check if a user is subscribed.
     *
     * @param $email
     * @param $list_id
     * @return bool
     */
    public function is_subscribed( $email = '', $list_id = null ) {
        $subscribed = $this->is_subscribed_by_cookie( $list_id );

        if ( $subscribed ) {
            return true;
        }

        if ( ! $email ) {
            return false;
        }

        return $this->is_subscribed_by_email( $email, $list_id );
    }
}