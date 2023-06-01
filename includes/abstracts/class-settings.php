<?php
/**
 * Settings class.
 *
 * @package NewsletterGate
 */

namespace NewsletterGate\Abstracts;

/**
 * Settings.
 */
abstract class Settings {

	/**
	 * Setting ID. Prefixes all options.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Page Title.
	 *
	 * @var string
	 */
	protected $page_title = '';

	/**
	 * Menu Title.
	 *
	 * @var string
	 */
	protected $menu_title = '';

	/**
	 * Menu Icon.
	 *
	 * @var string
	 */
	protected $menu_icon = '';

	/**
	 * Settings Fields.
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Parent Menu.
	 *
	 * @var null
	 */
	protected $parent = null;

	/**
	 * Constructor method.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Register a Menu Page.
	 *
	 * @return void
	 */
	public function register_page() {
		if ( $this->parent ) {
			add_submenu_page(
				$this->parent,
				$this->page_title,
				$this->menu_title ? $this->menu_title : $this->page_title,
				'manage_options',
				$this->id . '_options',
				array(
					$this,
					'render_page',
				)
			);
		} else {
			add_menu_page(
				$this->page_title,
				$this->menu_title ? $this->menu_title : $this->page_title,
				'manage_options',
				$this->id . '_options',
				array(
					$this,
					'render_page',
				),
				$this->menu_icon
			);
		}
	}

	/**
	 * Render the Settings Page.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2><?php echo esc_html( $this->page_title ); ?></h2>

			<form id="newslettergateForm" action="options.php" method="post">

				<?php

				do_action( $this->id . '_settings_before_options' );

				settings_fields( $this->id . '_options' );

				do_settings_sections( $this->id . '_options' );

				do_action( $this->id . '_settings_before_submit_button' );

				submit_button( __( 'Save Settings', 'newslettergate' ) );

				do_action( $this->id . '_settings_after_submit_button' );
				?>
			</form>
			<?php do_action( $this->id . '_settings_after_form' ); ?>
		</div>
		<?php
	}

	/**
	 * Get the settings ID.
	 *
	 * @return string
	 */
	public function get_settings_id() {
		return $this->id;
	}

	/**
	 * Add a field configuration to the array of fields.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 */
	public function add_field( $field ) {
		$this->fields = array_merge( $this->fields, $field );
	}

	/**
	 * Remove a section regarding these settings.
	 *
	 * This will remove the section only for the current page load when called.
	 *
	 * @param string $section Fields Section.
	 *
	 * @return void
	 */
	public function remove_section( $section ) {
		global $wp_settings_sections;

		$page = $this->id . '_options';

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		$section = 'section_' . $this->id . '_' . $section;

		if ( ! isset( $wp_settings_sections[ $page ][ $section ] ) ) {
			return;
		}

		unset( $wp_settings_sections[ $page ][ $section ] );
	}

	/**
	 * Remove a field regarding these settings.
	 *
	 * This will remove the section only for the current page load when called.
	 *
	 * @param array|string $field Field configuration to remove or field key to remove.
	 *
	 * @return void
	 */
	public function remove_field( $field ) {
		global $wp_settings_fields;

		if ( ! is_array( $field ) ) {
			$key    = $field;
			$fields = $this->get_fields();
			$field  = ! empty( $fields[ $key ] ) ? $fields[ $key ] : false;
		}

		if ( ! $field ) {
			return;
		}

		$page = $this->id . '_options';

		if ( ! isset( $wp_settings_fields[ $page ] ) ) {
			return;
		}

		$section = 'section_' . $this->id . '_' . $field['section'];

		if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		$field_id = 'option_' . $this->id . '_' . $field['name'];

		if ( ! isset( $wp_settings_fields[ $page ][ $section ][ $field_id ] ) ) {
			return;
		}

		unset( $wp_settings_fields[ $page ][ $section ][ $field_id ] );
	}

	/**
	 * Get Settings Fields.
	 *
	 * @return mixed|null
	 */
	public function get_fields() {
		return apply_filters( $this->id . '_get_settings_fields', $this->fields );
	}

	/**
	 * Register Fields
	 *
	 * @return void
	 */
	public function register_fields() {
		$fields = $this->get_fields();

		register_setting(
			$this->id . '_options',
			$this->id . '_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		foreach ( $fields as $field ) {
			if ( 'section' === $field['type'] ) {
				$field['before_section'] = '<div class="newslettergate-section ' . esc_attr( $field['name'] ) . '"><div class="newslettergate-section-title">';
				$field['after_section']  = '</div>';
				add_settings_section(
					'section_' . $this->id . '_' . $field['name'],
					$field['title'],
					array( $this, 'render_section' ),
					$this->id . '_options',
					$field
				);
			} else {
				add_settings_field(
					'option_' . $this->id . '_' . $field['name'],
					$field['title'],
					array( $this, 'render_field' ),
					$this->id . '_options',
					'section_' . $this->id . '_' . $field['section'],
					$field
				);
			}
		}
	}

	/**
	 * Render a section.
	 *
	 * @param array $args Section configuration.
	 *
	 * @return void
	 */
	public function render_section( $args ) {
		if ( ! empty( $args['description'] ) ) {
			?>
			<p class="description"><?php echo wp_kses_post( $args['description'] ); ?></p>
			<?php
		}
		// Closing section title div.
		?>
		</div>
		<?php
	}

	/**
	 * Get option key. Useful for name attributes in forms.
	 *
	 * @param string $key Field Name.
	 * @return string
	 */
	public function get_option_key( $key ) {
		return $this->id . '_options[' . $key . ']';
	}

	/**
	 * Get Option
	 *
	 * @param string $id Field Name.
	 * @param mixed  $default Default value if we don't have anything saved.
	 * @return mixed|string
	 */
	public function get_option( $id, $default = '' ) {
		$options = get_option( $this->id . '_options' );

		return isset( $options[ $id ] ) ? $options[ $id ] : $default;
	}

	/**
	 * Render Field
	 *
	 * @param array $args Field Arguments.
	 * @return void
	 */
	public function render_field( $args ) {
		if ( isset( $args['render'] ) ) {
			call_user_func_array( $args['render'], array( $args ) );
		} else {
			$this->{'render_' . $args['type'] }( $args );
		}
	}

	/**
	 * Render Text input
	 *
	 * @param array $args Field Arguments.
	 *
	 * @return void
	 */
	public function render_text( $args ) {
		$default = ! empty( $args['default'] ) ? $args['default'] : '';

		?>
		<input type="text" class="widefat" name="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>" value="<?php echo esc_attr( $this->get_option( $args['name'], $default ) ); ?>" />
		<?php
		if ( ! empty( $args['description'] ) ) {
			?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php
		}
	}

	/**
	 * Render Checkbox input
	 *
	 * @param array $args Field Arguments.
	 *
	 * @return void
	 */
	public function render_checkbox( $args ) {
		$option_checked = $this->get_option( $args['name'] );
		?>
		<label for="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>">
			<input <?php checked( $option_checked, 1, true ); ?> id="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>" value="1" />
			<?php echo esc_html( $args['description'] ); ?>
		</label>
		<?php
	}

	/**
	 * Render Checkbox input
	 *
	 * @param array $args Field Arguments.
	 *
	 * @return void
	 */
	public function render_multi_checkbox( $args ) {
		$selected_values = $this->get_option( $args['name'] );
		if ( ! is_array( $selected_values ) ) {
			$selected_values = array( $selected_values );
		}

		if ( ! empty( $args['description'] ) ) {
			?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php
		}
		?>
		<div class="newslettergate-scrollable">
			<?php
			foreach ( $args['options'] as $option_value => $option_text ) {
				$selected = in_array( $option_value, $selected_values, true );
				?>
				<p>
					<label for="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>_<?php echo esc_attr( $option_value ); ?>">
						<input
							id="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>_<?php echo esc_attr( $option_value ); ?>"
							<?php checked( $selected ); ?>
							type="checkbox"
							name="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>[]"
							value="<?php echo esc_attr( $option_value ); ?>"
						/>
						<?php echo esc_html( $option_text ); ?>
					</label>
				</p>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Color input
	 *
	 * @param array $args Field Arguments.
	 *
	 * @return void
	 */
	public function render_color( $args ) {
		$default = ! empty( $args['default'] ) ? $args['default'] : '';
		?>
		<input type="text" class="color-picker" name="<?php echo esc_attr( $this->get_option_key( $args['name'] ) ); ?>" value="<?php echo esc_attr( $this->get_option( $args['name'], $default ) ); ?>" />
		<?php
		if ( ! empty( $args['description'] ) ) {
			?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php
		}
	}

	/**
	 * Sanitize settings before saving them to the database.
	 *
	 * @param array $input Posted data.
	 *
	 * @return mixed
	 */
	public function sanitize( $input ) {
		$fields = $this->get_fields();

		foreach ( $fields as $field ) {
			// No need to sanitize sections.
			if ( 'section' === $field['type'] ) {
				continue;
			}

			if ( ! isset( $input[ $field['name'] ] ) ) {
				continue;
			}

			switch ( $field['type'] ) {
				case 'text':
					$input[ $field['name'] ] = sanitize_text_field( $input[ $field['name'] ] );
					break;
			}
		}

		do_action( $this->id . '_settings_sanitized', $input, $fields, $this );

		return $input;
	}
}
