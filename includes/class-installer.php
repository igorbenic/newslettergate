<?php
/**
 * Installer.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Class to perform creating database and other stuff
 *
 * @since  2.0.0
 */
class Installer {

	/**
	 * List of Updates.
	 * Array contains version => array of functions to call.
	 *
	 * @var array
	 */
	public $updates = array();

	/**
	 * Start the installation
	 *
	 * @return void
	 */
	public function install() {

		if ( ! defined( 'NEWSLETTERGATE_INSTALLING' ) ) {
			define( 'NEWSLETTERGATE_INSTALLING', true );
		}

		$this->maybe_save_version();
		$this->create_db();

	}

	/**
	 * Maybe save the version if there is no version installed.
	 *
	 * @since 2.20.1
	 */
	public function maybe_save_version() {
		// Add the version is the current one does not exist.
		if ( null === get_option( 'newslettergate_version', null ) ) {
			update_option( 'newslettergate_version', NEWSLETTERGATE_VERSION );
		}
	}

	/**
	 * Start the installation
	 *
	 * @param string $from_version From which version are we updating.
	 *
	 * @return void
	 */
	public function update( $from_version ) {

		if ( ! defined( 'NEWSLETTERGATE_UPDATING' ) ) {
			define( 'NEWSLETTERGATE_UPDATING', true );
		}

		foreach ( $this->updates as $version => $update_function ) {
			if ( version_compare( $from_version, $version, '<' ) ) {
				$update_function();
			}
		}

		update_option( 'newslettergate_version', NEWSLETTERGATE_VERSION );

	}

	/**
	 * Checking for version, updating if necessary
	 *
	 * @return void
	 */
	public function check_versions() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'newslettergate_version', null ) !== NEWSLETTERGATE_VERSION ) {

			$installer = new Installer();
			$installer->install();
			$installer->update( get_option( 'newslettergate_version', null ) );
			do_action( 'newslettergate_updated' );
		}
	}

	/**
	 * Create the Database
	 *
	 * @return void
	 */
	public function create_db() {

		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $this->get_schema() );
	}

	/**
	 * Get Table schema.
	 *
	 * @return string
	 */
	private function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		/*
		 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
		 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
		 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
		 *
		 * This may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
		 * indexes first causes too much load on some servers/larger DB.
		 */
		$max_index_length = 191;

		$tables = "
CREATE TABLE {$wpdb->prefix}newslettergate_subscribers (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  user_id bigint(20) DEFAULT 0,
  email longtext NOT NULL,
  list_id longtext NOT NULL,
  provider varchar(32) NOT NULL,
  ref_id varchar(32),
  date datetime,
  expires_at datetime,
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
) $collate";

		return $tables;
	}

}
