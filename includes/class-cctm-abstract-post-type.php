<?php
/**
 * CCTM Abstract Post Type file
 *
 * Developers can specify programmatically behavior for each post-type by creating
 * a file/class named after each post-type, e.g.
 *    CCTM_my_post_type.php
 *    class CCTM_my_post_type extends CCTM_Default_Post_Type { }
 *
 * OR they can globally override the default behavior globally for all post-types
 * by implementing the follwing file/class:
 *    CCTM_AllPostTypes.php
 *    class CCTM_AllPostTypes extends CCTM_Default_Post_Type { }
 *
 * AND devlopers could do both: globally override all default behavior and possibly
 * further override behavior based on post-type by doing both the above.
 *    CCTM_my_post_type.php
 *    class CCTM_my_post_type extends CCTM_AllPostTypes { }
 *
 * References:
 * http://www.phppatterns.com/docs/develop/php_and_variable_references
 * http://weierophinney.net/matthew/archives/131-Overloading-arrays-in-PHP-5.2.0.html
 *
 * @category Component
 * @package CCTM
 * @subpackage Include
 * @author Various
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */

/**
 * CCTM Abstract Post Type Class
 *
 * This class acts as an alias/pass-thru to the CCTM_Default_Post_Type class or any
 * optional overrides.  We require a point of abstraction here so we can
 * allow for optional overriding of the default classes and their behaviors.
 *
 * @category Component
 * @package CCTM
 * @subpackage Include
 * @author Various
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */
class CCTM_Abstract_Post_Type {
	/**
	 * Instance
	 *
	 * Creates instance.
	 *
	 * @var object $_instance An instance.
	 */
	private $_instance;


	// ------------------------------------------------------------------------------!
	/**
	 * __call
	 *
	 * Calls arrays.
	 *
	 * @param string $name Name.
	 * @param array  $args Arguments.
	 * @return array call_user_func_array User function array.
	 */
	public function __call( $name, $args = '' ) {
		return call_user_func_array( array( &$this->_instance, $name ), $args );
	}

	// ---------------------------------------------------------------------------!
	/**
	 * __construct
	 *
	 * A constructor.
	 *
	 * @param string $post_type The name of the post-type being instantiated.
	 */
	public function __construct( $post_type ) {
		// include the parent base class (i.e. the default).
		include_once CCTM_PATH . '/includes/class-cctm-default-post-type.php';

		$override = CCTM_PATH . '/addons/CCTM_AllPostTypes.php';
		if ( file_exists( $override ) ) {
			include_once $override;
			$class = 'CCTM_AllPostTypes';
		}

		// Check for an override for the given $post_type.
		$override = CCTM_PATH . '/addons/$post_type.php';
		if ( file_exists( $override ) ) {
			include_once $override;
			$class = 'CCTM_' . $post_type;
		}

		$this->_instance = new $class( $post_type );

	}

	// ----------------------------------------------------------------------------!
	/**
	 * __get
	 *
	 * Get function.
	 *
	 * @param string $name Instance name.
	 * @return string Instance name.
	 */
	public function __get( $name ) {
		return $this->_instance->$name;
	}
	// -----------------------------------------------------------------------------!
	/**
	 * __isset
	 *
	 * Test whether the instance name is set.
	 *
	 * @param string $name Name.
	 * @return string Instance name.
	 */
	public function __isset( $name ) {
		return isset( $this->_instance->$name );
	}
	// -----------------------------------------------------------------------------!
	/**
	 * __set
	 *
	 * Set instance name.
	 *
	 * @param string $name Name.
	 * @param string $value Value.
	 */
	public function __set( $name, $value ) {
		$this->_instance->$name = $value;
	}
	// -----------------------------------------------------------------------------!
	/**
	 * __unset
	 *
	 * Unset instance name.
	 *
	 * @param string $name Instance name for unsetting.
	 */
	public function __unset( $name ) {
		unset( $this->_instance->$name );
	}
}
/*EOF*/
