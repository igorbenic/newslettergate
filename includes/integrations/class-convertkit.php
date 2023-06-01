<?php
/**
 * ConvertKit class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate\integrations;

use NewsletterGate\Abstracts\API;

/**
 * ConvertKit class.
 */
class ConvertKit extends API {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'convertkit';

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.convertkit.com/v3/';

	/**
	 * Constructor method.
	 */
	public function __construct() {
		add_filter( 'newslettergate_get_settings_fields', array( $this, 'add_fields' ) );
	}

	/**
	 * Add fields for the integration.
	 *
	 * @param array $fields Fields configuration.
	 *
	 * @return mixed
	 */
	public function add_fields( $fields ) {
		$fields[] = array(
			'title' => __( 'ConvertKit', 'newslettergate' ),
			'type'  => 'section',
			'name'  => 'convertkit_section',
		);

		$fields[] = array(
			'title'       => __( 'Enable', 'newslettergate' ),
			'type'        => 'checkbox',
			'name'        => 'convertkit_enabled',
			'description' => __( 'Enable to be used for subscribing people for restricting content. If disabled, all content gated by ConvertKit will be shown.', 'newslettergate' ),
			'section'     => 'convertkit_section',
		);

		$fields[] = array(
			'title'   => __( 'API Secret Key', 'newslettergate' ),
			'type'    => 'text',
			'name'    => 'convertkit_api_key',
			'section' => 'convertkit_section',
		);

		$fields[] = array(
			'title'   => __( 'Lists', 'newslettergate' ),
			'type'    => 'lists',
			'name'    => 'convertkit_lists',
			'section' => 'convertkit_section',
			'render'  => array( $this, 'render_lists' ),
		);

		return $fields;
	}

	/**
	 * Render lists.
	 *
	 * @param array $args Field configuration.
	 *
	 * @return void
	 */
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

	/**
	 * Get the default headers for requests.
	 *
	 * @return array
	 */
	protected function get_default_headers() {
		return array(
			'Content-Type' => 'application/json; charset=utf-8',
		);
	}

	/**
	 * Get Integration lists.
	 *
	 * @return array|mixed|\WP_Error
	 */
	public function get_lists() {
		$lists = $this->get( 'forms?api_secret=' . $this->get_api_key() );

		if ( is_wp_error( $lists ) ) {
			return $lists;
		}

		$body = wp_remote_retrieve_body( $lists );

		return wp_list_pluck( json_decode( $body, true )['forms'], 'name', 'id' );
	}

	/**
	 * Prepare the response given from MailerLite.
	 *
	 * @param array|\WP_Error $response Response.
	 *
	 * @return mixed|\WP_Error
	 */
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

		return new \WP_Error( wp_remote_retrieve_response_code( $response ), __( 'Something went wrong.', 'newslettergate' ) );

	}

	/**
	 * Get Subscribers from a form.
	 *
	 * @param string $form_id Form ID.
	 * @param int    $page Page number.
	 *
	 * @return false|mixed
	 */
	public function get_subscribers_for_form( $form_id, $page = 1 ) {
		$members = $this->get( 'forms/' . $form_id . '/subscriptions?subscriber_state=active&api_secret=' . $this->get_api_key() . '&page=' . $page );

		if ( is_wp_error( $members ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $members ), true );
	}

	/**
	 * Find a subscriber in the ConvertKit form.
	 *
	 * @param string $email Email.
	 * @param mixed  $form_id Form ID.
	 *
	 * @return bool
	 */
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

		for ( $page = 2; $page <= $total_pages; $page++ ) {
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


	/**
	 * Check if the email is subscribed on this integration.
	 *
	 * @param string $email Email.
	 * @param mixed  $list_id List ID.
	 *
	 * @return bool
	 */
	public function is_subscribed_on_provider( $email, $list_id = null ) {
		return $this->find_subscriber_in_form( $email, $list_id );
	}

	/**
	 * Prepare body for the request.
	 *
	 * @param array $body Body for request.
	 *
	 * @return array|false|mixed|string
	 */
	protected function prepare_body( $body ) {
		return wp_json_encode( $body );
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
