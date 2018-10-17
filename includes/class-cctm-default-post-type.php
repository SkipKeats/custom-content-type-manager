<?php
/**
 * CCTM Default Post Type Class file
 *
 * PHP 7.2 plus
 *
 * @category Component
 * @package CCTM
 * @author Various
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */

/**
 * CCTM Default Post Type Class
 *
 * Implements the standard behavior for all custom post-types.
 *
 * @category Class
 * @package CCTM\includes
 * @author Various
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 */
class CCTM_Default_Post_Type {
	/**
	 * Post Type
	 *
	 * Post Type
	 *
	 * @var string $post_type Post type.
	 */
	public $post_type;

	/**
	 * __construct
	 *
	 * Constructor for post type.
	 *
	 * @param string $post_type Name of post-type.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
	}

	/**
	 * Print Custom Fields
	 */
	public function print_custom_fields() {
	}

	/**
	 * Get Definition
	 */
	public function get_definition() {}

	/**
	 * Draw Meta Boxes
	 */
	public function draw_meta_boxes() {}

	/**
	 * Define columns
	 */
	public function define_columns() {}

	/**
	 * Print admin header
	 *
	 * Called when ____________???
	 */
	public function print_admin_header() {
		// Show the big icon: http://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=136.
		if (
			isset( CCTM::$data['post_type_defs'][ $post_type ]['use_default_menu_icon'] )
			&& 0 === CCTM::$data['post_type_defs'][ $post_type ]['use_default_menu_icon'] ) {
			$baseimg = basename( CCTM::$data['post_type_defs'][ $post_type ]['menu_icon'] );
			// die($baseimg);.
			if ( file_exists( CCTM_PATH . '/images/icons/32x32/' . $baseimg ) ) {
				printf(
					'<style>
						#icon-edit, #icon-post {
						background-image:url(%s);
						background-position: 0px 0px;
						}
					</style>',
					CCTM_URL . '/images/icons/32x32/' . $baseimg
				);
			}
		}
	}
}
/*EOF*/
