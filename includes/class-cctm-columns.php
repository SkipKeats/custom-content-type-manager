<?php
/**
 * CCTM Columns file
 *
 * This handles custom columns when creating lists of posts/pages in the manager.
 * We use an object here so we can rely on a "dynamic funciton name" via __call() where
 * the function called corresponds to the post-type name.
 *
 * WARNING: This requires that the post-type is named validly, i.e. in a name that would
 * be valid as a PHP function.
 * See
 * http://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
 *
 * In WP 3.1, manage_edit-${post_type}_columns has been supplanted by manage_${post_type}_posts_columns.
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
 * CCTM Columns Class
 *
 * This handles custom columns when creating lists of posts/pages in the manager.
 *
 * @category Class
 * @package CCTM
 * @subpackage Include
 * @author Various
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */
class CCTM_Columns {

	/**
	 * Post Type
	 *
	 * Sets the post-type, e.g. 'books'.
	 *
	 * @var string $post_type Post type.
	 */
	public $post_type;

	/**
	 * No Title Flag
	 *
	 * Goes to true if the custom columns do not include the built-in title field.
	 *
	 * @var bool $no_title_flag Sets title flag.
	 */
	public $no_title_flag = false;

	/*
	 * I use these to count as I iterate over columns in each row. new_row triggers a break...
	 * req'd due to some WP wonkyness.
	 */

	/**
	 * New row
	 *
	 * Sets new row
	 *
	 * @var bool $new_row Creates a new row.
	 */
	public $new_row = true;

	/**
	 * Last Post
	 *
	 * Holds last post.
	 *
	 * @var string $last_post The last post.
	 */
	public $last_post;

	/**
	 * __call
	 *
	 * This is the magic function, named after the post-type in question.
	 * Return value is an associative array where the element key is the name of the column,
	 * and the value is the header text for that column.
	 *
	 * @param string $post_type Post type.
	 * @param array  $default_columns Associative array (set by WP).
	 * @return array Associative array of column ids and translated names for header names.
	 */
	public function __call( $post_type, $default_columns ) {

		$this->post_type = $post_type;
		// print_r( $default_columns ); exit;.
		$default_columns['author'] = 'Author';
		$custom_columns            = array( 'cb' => '<input type="checkbox" />' );
		$raw_columns               = array();
		if ( isset( CCTM::$data['post_type_defs'][ $post_type ]['cctm_custom_columns'] ) ) {
			$raw_columns = CCTM::$data['post_type_defs'][ $post_type ]['cctm_custom_columns'];
		}

		// print_r($raw_columns); exit;.

		/**
		 * The $raw_columns contains a simple array, e.g. array('field1','wonky');
		 * we need to create an associative array.
		 * Look up what kind of column this is.
		 *
		 * @var array $raw_columns Array.
		 */
		foreach ( $raw_columns as $c ) {

			if ( isset( $default_columns[0][ $c ] ) ) {
				$custom_columns[ $c ] = $default_columns[0][ $c ]; // Already translated.
			} elseif ( isset( CCTM::$data['custom_field_defs'][ $c ] ) ) {
				// Custom Field.
				$custom_columns[ $c ] = __( CCTM::$data['custom_field_defs'][ $c ]['label'] );
			} elseif ( 'author' === $c ) {
				// Author. See https://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=456.
				$custom_columns[ $c ] = __( 'Author' );
			} elseif ( 'category' === $c ) {
				/*
				 * Taxonomies: moronically, WP sends this function plurals instead of taxonomy slugs.
				 * so we have to manually remap this. *facepalm*.
				 */
				$custom_columns['categories'] = $default_columns[0]['categories'];
			} elseif ( 'post_tag' === $c ) {
				$custom_columns['tags'] = $default_columns[0]['tags'];
			} else {
				// custom taxonomies.
				$tax = get_taxonomy( $c );
				if ( $tax ) {
					$custom_columns[ $c ] = $tax->labels->name;
				}
			}
		}

		if ( ! isset( $custom_columns['title'] ) ) {
			$this->no_title_flag = true;
		}
		return $custom_columns;
	}

	/**
	 * Populate Custom Column Data
	 *
	 * Populate the custom data for a given column.  This function should actually
	 * *print* data, not just return it.
	 * Oddly, WP doesn't even send the column this way unless it is something custom.
	 * Note that things get all broken and wonky if you do not include the "post_title"
	 * column, so we rely on the $this->no_title_flag boolean variable (set in __call)
	 * to trigger customizations here which print out the various eye-candy
	 * "Edit/Trash/View" links when the post_title column has been omitted.
	 *
	 * @link https://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=443
	 * @param string $column Name.
	 */
	public function populate_custom_column_data( $column ) {

		// See https://code.google.com/p/wordpress-custom-content-type-manager/wiki/CustomColumns.
		$function_name = 'cctm_custom_column_' . $this->post_type . '_' . $column;
		if ( function_exists( $function_name ) ) {
			return $function_name();
		}

		global $post;

		if ( $this->last_post !== $post->ID ) {
			$this->new_row = true;
		}

		// This attaches the Edit/Trash/View links to the first column if the post_title isn't there.
		if ( $this->no_title_flag && $this->new_row ) {
			printf(
				'<strong><a class="row-title" href="post.php?post=%s&amp;action=edit">',
				$post->ID
			);
		}

		$meta = get_custom_field_meta( $column );
		if ( 'image' === $meta['type'] ) {
			$id = get_custom_field( $column . ':raw' );
			printf(
				'<a target="_blank" href="%s">%s</a>',
				get_edit_post_link( $id ),
				wp_get_attachment_image( $id, 'thumbnail' )
			);
		} elseif ( 'relation' === $meta['type'] ) {
			$id = get_custom_field( $column . ':raw' );
			printf(
				'<a target="_blank" href="%s">%s</a>',
				get_edit_post_link( $id ),
				get_the_title( $id )
			);
		} else { // Uses the default output filter.
			print_custom_field( $column );
		}

		// End the anchor.
		if ( $this->no_title_flag && $this->new_row ) {
			print '</a></strong>
				<div class="row-actions">
					<span class="edit">
						<a href="post.php?post=' . $post->ID . '&amp;action=edit" title="' . __( 'Edit' ) . '">' . __( 'Edit' ) . '</a> |
					</span>
					<span class="inline hide-if-no-js">
						<a href="#" class="editinline">' . __( 'Quick Edit' ) . '</a> |
					</span>
					<span class="trash">
						<a class="submitdelete" href="' . get_delete_post_link( $post->ID ) . '">' . __( 'Trash' ) . '</a> |
					</span>
					<span class="view">
						<a href="' . get_permalink( $post->ID ) . '" rel="permalink">' . __( 'View' ) . '</a>
					</span>';
			$this->new_row   = false;
			$this->last_post = $post->ID;
		}
	}
}
/*EOF*/
