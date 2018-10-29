<?php
/**
 * CCTM Validator file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Validator
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */

/**
 * CCTM Validator Class
 *
 * Abstract class for validation rules.  Classes that validate stuff.
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Validator
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */
abstract class CCTM_Validator {

	/**
	 * Validation Options Array
	 *
	 * Properties of this validator, as defined and submitted in the get_options() function
	 * published here for easy class-wide access.  Override this to set default values.
	 *
	 * @var array $options Options array.
	 */
	public $options = array();

	/**
	 * Validation Error Message
	 *
	 * If a field does not validate, set this message.
	 *
	 * @var string $error_msg The response generated for a given error.
	 */
	public $error_msg;

	/**
	 * Validation Error Message Subject
	 *
	 * The name of the field being validated, used for error messages.
	 *
	 * @var string $subject The title of the message.
	 */
	public $subject;

	/**
	 * Show in Menus, or Not
	 *
	 * Most validation rules should be publicly visible when you define a field,
	 * but if desired, you can hide a rule from the menu.
	 *
	 * @var bool $show_in_menus A toggle.
	 */
	public $show_in_menus = true;

	// -----------------------------------------------------------------------------!
	/**
	 * Get
	 *
	 * Retrieve error info.
	 *
	 * @param string $key A key from a key/value pair.
	 */
	public function __get( $key ) {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}

		return '';
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Ket Set
	 *
	 * Is the key set?
	 *
	 * @param string $key A key from a key/value pair.
	 */
	public function __isset( $key ) {
		if ( isset( $this->options[ $key ] ) ) {
			return true;
		}
		return false;
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Set Key/Pair
	 *
	 * Set the key pair.
	 *
	 * @param string $key A key from a key/value pair.
	 * @param mixed  $value The value of the key.
	 */
	public function __set( $key, $value ) {
		$this->options[ $key ] = $value;
	}

	/**
	 * Get Description
	 *
	 * A description of what the validation rule is and does.
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Get Name
	 *
	 * Returns the human-readable name of the validation rules.
	 *
	 * @return string
	 */
	abstract public function get_name();


	/**
	 * Get the HTML Options
	 *
	 * Implement this if your validation rule requires some options: this should
	 * return some form elements that will dynamically be shown on the page via
	 * an AJAX request if this validator is selected. Do not include the entire form, just the inputs you need!
	 * Form fields should use names that are nodes of $_POST['validator_options'], e.g.
	 *     <input name="validator_options[option1]" />
	 *
	 * @return string HTML form elements.
	 */
	public function get_options_html() {
		return __( 'No configurable options.', CCTM_TXTDOMAIN );
	}

	/**
	 * Validate
	 *
	 * Run the rule: check the user input. Return the (filtered) value that should
	 * be used to repopulate the form.  If an $input is invalid, this function should
	 * set the $this->error_msg, e.g.
	 *
	 * if ($input == 'bad') {
	 *    $this->error_msg = __('The input was bad.');
	 * }
	 *
	 * @param string $input the value of the field being validated (as it is stored in the database).
	 * @return string The filtered input after validation.  Usually you want to leave this as the $input.
	 */
	abstract public function validate( $input );

	// -----------------------------------------------------------------------------!
	// Public functions!
	// -----------------------------------------------------------------------------!

	/**
	 * Draw Options
	 *
	 * Draws the metabox containing the validators description and any options.
	 *
	 * @return string html
	 */
	public function draw_options() {

		return sprintf(
			'<div class="postbox">
				<h3 class="hndle"><span>%s</span></h3>
				<div class="inside">
					<p>%s</p>
					%s
				</div>
			</div>',
			__( 'Validation Configuration', CCTM_TXTDOMAIN ),
			$this->get_description(),
			$this->get_options_html()
		);
	}

	/**
	 * Get Error Message
	 *
	 * Retrieve error message
	 *
	 * @return string Error message.
	 */
	public function get_error_msg() {
		return $this->error_msg;
	}

	/**
	 * Get Field ID
	 *
	 * Get a unique field ID for a field element, same idea as WP Widget functions.
	 *
	 * @param string $id Field element ID.
	 * @return string
	 */
	public function get_field_id( $id ) {
		return "validator_options_$id";
	}

	/**
	 * Get Field Name
	 *
	 * Get a unique field name for a field element in the validation options html,
	 * same idea as WP Widget functions.
	 *
	 * @param string $name Field name.
	 * @return string
	 */
	public function get_field_name( $name ) {
		return "validator_options[ $name ]";
	}

	/**
	 * Get Subject
	 *
	 * Name of the field being validated.
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 * Get Value
	 *
	 * Retrieve key values.
	 *
	 * @param string $key name inside of $this->options.
	 * @return string the value.
	 */
	public function get_value( $key ) {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		} else {
			return '';
		}
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Is the Option Set
	 *
	 * Tests whether option is set.
	 *
	 * @param string $key inside of $this->options.
	 * @return string depending on whether the option is set (i.e. checked).
	 */
	public function is_checked( $key ) {
		if ( isset( $this->options[ $key ] ) && $this->options[ $key ] ) {
			return ' checked="checked"';
		} else {
			return '';
		}
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Is Option Selected
	 *
	 * Test for whether item is set.
	 *
	 * @param string $key inside of $this->options.
	 * @return string depending on whether the option is set (i.e. checked).
	 */
	public function is_selected( $key ) {
		if ( isset( $this->options[ $key ] ) && $this->options[ $key ] ) {
			return ' selected="selected"';
		} else {
			return '';
		}
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Set Subject
	 *
	 * Used for validation messaging.
	 *
	 * @param string $fieldname of the field being validated.
	 */
	public function set_subject( $fieldname ) {
		$this->subject = $fieldname;
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Set Options
	 *
	 * Set the current options for this validator.
	 *
	 * @param array $options Options array.
	 */
	public function set_options( $options ) {
		$this->options = $options;
	}

}
/*EOF*/
