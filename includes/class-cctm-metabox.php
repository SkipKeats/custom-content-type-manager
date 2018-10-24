<?php
/**
 * CCTM Metabox Class file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Metabox
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.0.0.0
 */

/**
 * CCTM Metabox Class
 *
 * Contains functions particular to creating/editing/managing metaboxes.
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Metabox
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.0.0.0
 */
class CCTM_Metabox {

	/**
	 * Error Array
	 *
	 * Variable to hold the error array.
	 *
	 * @var array $errors The error array.
	 */
	public static $errors = array();

	/**
	 * Get the Metabox Holder
	 *
	 * Function obtains the metabox holder.
	 *
	 * @param string $metabox_name Name of metabox.
	 * @param array  $items Metabox fields.
	 * @return string
	 */
	public static function get_metabox_holder( $metabox_name, $items ) {
		$d          = array();
		$d['items'] = implode( '', $items );
		$d['title'] = __( 'Custom Fields', CCTM_TXTDOMAIN );
		if ( isset( CCTM::$data['metabox_defs'][ $metabox_name ]['title'] ) ) {
			$d['title'] = __( CCTM::$data['metabox_defs'][ $metabox_name ]['title'] );
		}
		$d['metabox']           = $metabox_name;
		$d['edit_metabox_link'] = get_site_url() . '/wp-admin/admin.php?page=cctm&a=edit_metabox&id=' . $metabox_name;
		return CCTM::load_view( 'metabox-holder.php', $d );
	}

	/**
	 * Is Definition Valid?
	 *
	 * Tests a definition to ensure it's valid.
	 *
	 * @param array $def Definition.
	 * @param bool  $is_update True when updating instead of creating.
	 * @return boolean
	 */
	public static function is_valid_def( $def, $is_update = false ) {

		// Required fields in place?
		if ( ! isset( $def['id'] ) || empty( $def['id'] ) ) {
			self::$errors['id'] = __( 'ID is a required field.', CCTM_TXTDOMAIN );
		} else {
			// id already taken?
			if ( $is_update ) {
				// Print_r($def); exit;.
				if ( isset( CCTM::$data['metabox_defs'][ $def['id'] ] ) && $def['id'] !== $def['old_id'] ) {
					self::$errors['id'] = __( 'That ID is already taken. Please choose another.', CCTM_TXTDOMAIN );
				} elseif ( preg_match( '/[^a-z\_\-]/i', $def['id'] ) ) {
					self::$errors['id'] = __( 'ID contains invalid characters.', CCTM_TXTDOMAIN );
				}
			} else {
				if ( isset( CCTM::$data['metabox_defs'][ $def['id'] ] ) ) {
					self::$errors['id'] = __( 'That ID is already taken. Please choose another.', CCTM_TXTDOMAIN );
				} elseif ( preg_match( '/[^a-z\_\-]/i', $def['id'] ) ) {
					self::$errors['id'] = __( 'ID contains invalid characters.', CCTM_TXTDOMAIN );
				}
			}
		}
		if ( ! isset( $def['title'] ) || empty( $def['id'] ) ) {
			self::$errors['title'] = __( 'Title is a required field.', CCTM_TXTDOMAIN );
		}
		// Callback function exists? is_callable handles static methods as well as functions.
		if ( isset( $def['callback'] ) && ! empty( $def['callback'] ) ) {
			if ( is_callable( $def['callback'] ) ) {
				if ( ! strncmp( $def['callback'], 'CCTM', 4 ) ) {
					self::$errors['callback'] = __( 'You cannot use a CCTM function as your callback.', CCTM_TXTDOMAIN );
				}
			} else {
				self::$errors['callback'] = __( 'The callback function does not exist.', CCTM_TXTDOMAIN );
			}
		}

		// Did we survive the gauntlet?
		if ( empty( self::$errors ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sanitize!
	 *
	 * Sanitize data.
	 *
	 * @param array $posted_data Data for posting.
	 * @return array
	 */
	public static function sanitize( $posted_data ) {

		$data                  = array();
		$data['id']            = CCTM::get_value( $posted_data, 'id' );
		$data['old_id']        = CCTM::get_value( $posted_data, 'old_id' );
		$data['title']         = CCTM::get_value( $posted_data, 'title' );
		$data['context']       = CCTM::get_value( $posted_data, 'context' );
		$data['priority']      = CCTM::get_value( $posted_data, 'priority' );
		$data['callback']      = CCTM::get_value( $posted_data, 'callback' );
		$data['callback_args'] = CCTM::get_value( $posted_data, 'callback_args' );
		// See https://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=511.
		$data['visibility_control'] = ( isset( $posted_data['visibility_control'] ) ) ? $posted_data['visibility_control'] : '';
		$data['post_types']         = CCTM::get_value( $posted_data, 'post_types', array() );

		$data = CCTM::striptags_deep( $data );
		$data = CCTM::stripslashes_deep( $data );

		return $data;
	}
}

/*EOF*/
