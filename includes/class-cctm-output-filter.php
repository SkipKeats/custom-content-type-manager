<?php
/**
 * CCTM Form Element file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Output_Filter
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.8.0
 */

/**
 * CCTM Form Element Class
 *
 * Abstract class for standardizing output filters.
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Output_Filter
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.8.0
 */
abstract class CCTM_Output_Filter {

	/**
	 * Show in Menus?
	 *
	 * Most filters should be publicly visible, but some should only be used via direct invocation.
	 *
	 * @var bool $show_in_menus Toggle for showing in menus.
	 */
	public $show_in_menus = true;

	/**
	 * Is the Input an Array?
	 *
	 * Tracks what whether the input sent to the to_array function was an array or not.
	 *
	 * @var null $is_array_input Is input an array?
	 */
	public $is_array_input = null;

	/**
	 * Filter
	 *
	 * Apply the filter.
	 *
	 * @param mixed $input Input.
	 * @param mixed $options Optional arguments.
	 * @return mixed
	 */
	abstract public function filter( $input, $options = null );

	/**
	 * Get Description
	 *
	 * A description of what the filter is and does.
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Get Example
	 *
	 * Show the user how to use the filter inside a template file.
	 *
	 * @param string $fieldname The field name.
	 * @param string $fieldtype Field type.
	 * @param bool   $is_repeatable Is the field repeatable.
	 * @return string
	 */
	abstract public function get_example( $fieldname = 'my_field', $fieldtype, $is_repeatable = false );

	/**
	 * Get Name
	 *
	 * The human-readable name of the filter.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get URL
	 * The URL where the user can read more about the filter.
	 *
	 * @return string
	 */
	abstract public function get_url();

	/**
	 * Converts Input to Array
	 *
	 * Converts an input to an array -- this handles strings, PHP arrays, and JSON arrays.
	 * This function is useful for any field that may need to handle both single and
	 * "repeatable" inputs.
	 *
	 * @param mixed $input The input.
	 * @return array
	 */
	public function to_array( $input ) {

		if ( '[""]' === $input ) {
			$this->is_array_input = true;
			return array();
		} elseif ( '' === $input ) {
			$this->is_array_input = false;
			return array();
		}

		$the_array = array();

		if ( is_array( $input ) ) {
			$this->is_array_input = true;
			return $input; // No JSON converting necessary: PHP array supplied.
		} else {
			// This will destroy the input if it's not json, but behavior varies between PHP versions.
			// https://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=557.
			if ( ! is_array( $input ) ) {
				$first_char = mb_substr( $input, 0, 1, 'utf-8' );
				if ( '{' === $first_char || '[' === $first_char ) {
					$output = (array) json_decode( $input, true );
				}
			}

			// See http://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=121.
			if ( ! is_array( $output ) ) {
				$this->is_array_input = false;
				if ( empty( $output ) && ! empty( $input ) ) {
					return array( $input );
				} else {
					return array( $output );
				}
			} else {
				$this->is_array_input = true;
				return $output;
			}
		}
	}

}
/*EOF*/
