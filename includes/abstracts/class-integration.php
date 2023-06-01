<?php
/**
 * Integration class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate\Abstracts;

use NewsletterGate\DB;

/**
 * Class definition for Integration.
 */
abstract class Integration {

	/**
	 * Integration ID.
	 *
	 * @var null
	 */
	protected $id = null;

	/**
	 * Get the Integration ID.
	 *
	 * @return null
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Return if the integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return absint( $this->get_option( 'enabled', '0' ) ) === 1;
	}

	/**
	 * Get the option we need from all the saved options.
	 *
	 * @param string $key Option name.
	 * @param mixed  $default Default value if no option is found.
	 *
	 * @return mixed|string
	 */
	public function get_option( $key, $default = null ) {
		$key = $this->id . '_' . $key;
		return newslettergate()->settings->get_option( $key, $default );
	}

	/**
	 * Return the number of cookies for this integration.
	 *
	 * @return int|mixed
	 */
	public function get_number_of_integration_cookies() {
		return isset( $_COOKIE[ 'ngate_' . $this->id . '_count' ] ) ? absint( $_COOKIE[ 'ngate_' . $this->id . '_count' ] ) : 0;
	}

	/**
	 * Get all cookies for the selected integration.
	 *
	 * @return array
	 */
	public function get_cookies_for_integration() {
		$found_cookies     = array();
		$number_of_cookies = $this->get_number_of_integration_cookies();

		if ( ! $number_of_cookies ) {
			return $found_cookies;
		}

		$cookie_prefix = 'ngate_' . $this->id;

		for ( $c = 1; $c <= $number_of_cookies; $c++ ) {
			$cookie_name = $cookie_prefix . '_' . $c;

			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				$found_cookies[ $cookie_name ] = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
			}
		}

		return $found_cookies;
	}

	/**
	 * Return if the current visitor is subscribed by cookie.
	 *
	 * @param mixed $list_id List ID.
	 *
	 * @return bool
	 */
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

	/**
	 * Return if the provided email is subscribed to the selected list.
	 *
	 * @param string $email Email.
	 * @param mixed  $list_id List ID.
	 *
	 * @return bool
	 */
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
	 * @param string $email Email to check.
	 * @param mixed  $list_id List ID.
	 *
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
