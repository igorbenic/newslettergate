<?php
/**
 * Mailerlite Integration class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate\integrations;

use NewsletterGate\Abstracts\API;

/**
 * Mailerlite integration.
 */
class Mailerlite extends API {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	protected $id = 'mailerlite';

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.mailerlite.com/api/v2/';

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
			'title' => __( 'Mailerlite', 'newslettergate' ),
			'type'  => 'section',
			'name'  => 'mailerlite_section',
		);

		$fields[] = array(
			'title'       => __( 'Enable', 'newslettergate' ),
			'type'        => 'checkbox',
			'name'        => 'mailerlite_enabled',
			'description' => __( 'Enable to be used for subscribing people for restricting content. If disabled, all content gated by Mailerlite will be shown.', 'newslettergate' ),
			'section'     => 'mailerlite_section',
		);

		$fields[] = array(
			'title'   => __( 'API Key', 'newslettergate' ),
			'type'    => 'text',
			'name'    => 'mailerlite_api_key',
			'section' => 'mailerlite_section',
		);

		$fields[] = array(
			'title'   => __( 'Lists', 'newslettergate' ),
			'type'    => 'lists',
			'name'    => 'mailerlite_lists',
			'section' => 'mailerlite_section',
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
						<code>[newslettergate provider=mailerlite list=<?php echo esc_html( $list_id ); ?>]Content To Gate[/newslettergate]</code>
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
			'Content-Type'        => 'application/json',
			'X-MailerLite-ApiKey' => $this->get_api_key(),
		);
	}

	/**
	 * Get MailerLite lists.
	 *
	 * @return array|mixed|\WP_Error
	 */
	public function get_lists() {
		$lists = $this->get( 'groups' );

		if ( is_wp_error( $lists ) ) {
			return $lists;
		}

		$body = wp_remote_retrieve_body( $lists );

		return wp_list_pluck( json_decode( $body, true ), 'name', 'id' );
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
			return new \WP_Error( wp_remote_retrieve_response_code( $response ), $body['error']['message'] );
		}

		return new \WP_Error( wp_remote_retrieve_response_code( $response ), __( 'Something went wrong.', 'newslettergate' ) );

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
		$resp = $this->get( 'subscribers/' . $email . '/groups' );
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
			'email'          => $email,
			'resubscribe'    => true,
			'autoresponders' => true,
			'type'           => 'active',
		);

		$post = $this->post( 'groups/' . $list_id . '/subscribers', $body );

		if ( ! is_wp_error( $post ) ) {
			return true;
		}

		return $post;
	}
}
