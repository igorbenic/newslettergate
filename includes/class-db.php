<?php
/**
 * Database class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

/**
 * DB class.
 */
class DB {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table_name = 'newslettergate_subscribers';

	/**
	 * Get the table name with the prefix.
	 *
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	/**
	 * Get records by columns.
	 *
	 * @param array $columns key => value pair for columns.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_by_columns( $columns ) {
		global $wpdb;

		$select       = 'SELECT * FROM ' . $this->get_table_name();
		$where_clause = ' WHERE 1=1';
		foreach ( $columns as $column => $value ) {

			if ( is_array( $value ) ) {
				$value   = array_unique( $value );
				$escaped = array();
				foreach ( $value as $non_escaped ) {
					if ( is_numeric( $non_escaped ) ) {
						$escaped[] = $wpdb->prepare( '%d', $non_escaped );
					} else {
						$escaped[] = $wpdb->prepare( '%s', $non_escaped );
					}
				}
				$escaped_value = implode( ',', $escaped );
			} else {
				if ( is_numeric( $value ) ) {
					$escaped_value = $wpdb->prepare( '%d', $value );
				} else {
					$escaped_value = $wpdb->prepare( '%s', $value );
				}
			}

			$where_clause .= " AND {$column} " . ( is_array( $value ) ? ' IN (' . $escaped_value . ')' : '=' . $escaped_value );
		}

		return $wpdb->get_results( $select . $where_clause, ARRAY_A ); // phpcs:ignore -- Reason: It is already prepared.
	}

	/**
	 * Get by a single column.
	 *
	 * @param string $column Column name.
	 * @param mixed  $value Value to check against.
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_by_column( $column, $value ) {
		return $this->get_by_columns( array( $column => $value ) );
	}
}
