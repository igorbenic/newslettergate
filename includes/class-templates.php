<?php
/**
 * Templates class for handling everything around loading templates.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate;

/**
 * Templates class.
 */
class Templates {

	/**
	 * Locate a template and return the path for inclusion.
	 *
	 * This is the load order:
	 *
	 * yourtheme/$template_path/$template_name
	 * yourtheme/$template_name
	 * $default_path/$template_name
	 *
	 * Copied from WooCommerce
	 *
	 * @param string $template_name Template name.
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 * @return string
	 */
	public function locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = newslettergate()->template_path();
		}

		if ( ! $default_path ) {
			$default_path = newslettergate()->plugin_path() . '/templates/';
		}

		if ( empty( $template ) ) {
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				)
			);
		}

		// Get default template/.
		if ( ! $template ) {
			if ( empty( $cs_template ) ) {
				$template = $default_path . $template_name;
			} else {
				$template = $default_path . $cs_template;
			}
		}

		// Return what we found.
		return apply_filters( 'newslettergate_locate_template', $template, $template_name, $template_path );
	}

	/**
	 * Get other templates (e.g. product attributes) passing attributes and including the file.
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 */
	public function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		$template = $this->locate_template( $template_name, $template_path, $default_path );

		// Allow 3rd party plugin filter template file from their plugin.
		$filter_template = apply_filters( 'newslettergate_get_template', $template, $template_name, $args, $template_path, $default_path );

		if ( $filter_template !== $template ) {
			if ( ! file_exists( $filter_template ) ) {
				/* translators: %s template */
				_doing_it_wrong( __FUNCTION__, wp_kses_post( sprintf( __( '%s does not exist.', 'woocommerce' ), '<code>' . $filter_template . '</code>' ) ), '1.2.1' );
				return;
			}
			$template = $filter_template;
		}

		$action_args = array(
			'template_name' => $template_name,
			'template_path' => $template_path,
			'located'       => $template,
			'args'          => $args,
		);

		if ( ! empty( $args ) && is_array( $args ) ) {
			if ( isset( $args['action_args'] ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					esc_html__( 'action_args should not be overwritten when calling wc_get_template.', 'woocommerce' ),
					'3.6.0'
				);
				unset( $args['action_args'] );
			}
            extract( $args ); // @codingStandardsIgnoreLine
		}

		do_action( 'newslettergate_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

		include $action_args['located'];

		do_action( 'newslettergate_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
	}
}
