<?php

namespace NewsletterGate;

class DB {

    protected $table_name = 'newslettergate_subscribers';

    public function get_table_name() {
        global $wpdb;

        return $wpdb->prefix . $this->table_name;
    }

    public function get_by_columns( $columns ) {
        global $wpdb;

        $where_clause = ' WHERE 1=1';
        foreach ( $columns as $column => $value ) {

            if ( is_array( $value ) ) {
                $escaped = [];
                foreach ( $value as $non_escaped ) {
                    if ( is_numeric( $non_escaped ) ) {
                        $escaped[] = $wpdb->prepare( '%d', $non_escaped );
                    } else {
                        $escaped[] = $wpdb->prepare( '%s', $non_escaped );
                    }
                }
                $escaped_value = implode(',', $escaped);
            } else {
                if ( is_numeric( $value ) ) {
                    $escaped_value = $wpdb->prepare( '%d', $value );
                } else {
                    $escaped_value = $wpdb->prepare( '%s', $value );
                }
            }

            $where_clause .= " AND {$column} " . ( is_array( $value ) ? " IN (" . $escaped_value . ")" : "=" . $escaped_value );
        }

        return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %s' . $where_clause, $this->get_table_name() ), ARRAY_A );
    }

    public function get_by_column( $column, $value ) {
        return $this->get_by_columns([ $column => $value ]);
    }
}