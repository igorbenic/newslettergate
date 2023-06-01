<?php
/**
 * Subscriber Class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

/**
 * Subscriber Class to handle finding subscribers and saving data.
 */
class Subscriber {

	/**
	 * Subscriber ID from the Table.
	 *
	 * @var null|int
	 */
	protected $id = null;

	/**
	 * Subscriber Data.
	 *
	 * @var null|array
	 */
	protected $data = null;

	/**
	 * Set the Data.
	 *
	 * @param array $data Subscriber Data.
	 *
	 * @return void
	 */
	public function set_data( $data ) {
		$this->data = $data;
	}

	/**
	 * Get the Data.
	 *
	 * @return array|null
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Find a subscriber by email, provider and list ID.
	 *
	 * @param string $email Email.
	 * @param string $provider Provider/Integration ID.
	 * @param mixed  $list_id List ID.
	 *
	 * @return $this
	 */
	public function maybe_find_subscriber( $email, $provider, $list_id ) {
		$db = new DB();
		$this->set_data(
			array(
				'email'    => $email,
				'provider' => $provider,
				'list_id'  => $list_id,
			)
		);
		$subs = $db->get_by_columns(
			array(
				'email'    => $email,
				'provider' => $provider,
				'list_id'  => $list_id,
			)
		);

		if ( $subs ) {
			foreach ( $subs as $sub ) {
				$this->id   = $sub['id'];
				$this->data = $sub;
				return $this;
			}
		}

		return $this;
	}

	/**
	 * Save the Subscriber to the table.
	 * If we have the ID, update it instead.
	 *
	 * @return void
	 */
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

		$provider    = $this->data['provider'];
		$integration = newslettergate()->get_integration( $provider );

		if ( ! $integration ) {
			return;
		}

		$cookies = $integration->get_cookies_for_integration();

		$db   = new DB();
		$subs = $db->get_by_columns(
			array(
				'email'    => $this->data['email'],
				'provider' => $this->data['provider'],
				'list_id'  => $this->data['list_id'],
			)
		);

		if ( $cookies ) {
			foreach ( $cookies as $cookie_value ) {
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
		setcookie( 'ngate_' . $this->data['provider'] . '_count', $new_cookie_order, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Update the Subscriber Data.
	 *
	 * @return void
	 */
	public function update() {
		global $wpdb;

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + MONTH_IN_SECONDS );

		$wpdb->update( // phpcs:ignore -- Reason: Need to update custom Table wih subscriber data.
			$wpdb->prefix . 'newslettergate_subscribers',
			array(
				'expires_at' => $expires_at,
			),
			array(
				'id' => $this->id,
			)
		);
	}

	/**
	 * Add a subscriber to the table.
	 *
	 * @return void
	 */
	public function add() {
		global $wpdb;

		$this->data['ref_id'] = md5( $this->data['email'] . $this->data['list_id'] . $this->data['provider'] );

		$wpdb->insert( // phpcs:ignore -- Reason: Need to insert into custom table wih subscriber data.
			$wpdb->prefix . 'newslettergate_subscribers',
			array(
				'ref_id'     => $this->data['ref_id'],
				'user_id'    => get_current_user_id(),
				'list_id'    => $this->data['list_id'],
				'email'      => $this->data['email'],
				'provider'   => $this->data['provider'],
				'date'       => current_time( 'mysql' ),
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + MONTH_IN_SECONDS ),
			)
		);
	}
}
