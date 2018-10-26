<?php
/**
 * CCTM Post Widget file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Post_Widget
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */

/**
 * CCTM Post Widget Class
 *
 * This widget is designed to allow users to display content from a single post in a widget.
 * Functionality inspired by the Custom Post Widget plugin: http://wordpress.org/extend/plugins/custom-post-widget/.
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Post_Widget
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */
class CCTM_Post_Widget extends WP_Widget {

	/**
	 * Widget Instance Name
	 *
	 * Hold name of post widget instance.
	 *
	 * @var string $name Widget instance name.
	 */
	public $name;

	/**
	 * Widget Instance Description
	 *
	 * Description of widget instance.
	 *
	 * @var string $description Widget instance description.
	 */
	public $description;

	/**
	 * Widget Control Options
	 *
	 * Widget instance control options.
	 *
	 * @var array $control_options Control options.
	 */
	public $control_options = array(
		'title' => 'Post',
	);

	/**
	 * Constructor Function
	 *
	 * Assembles the widget parts.
	 */
	public function __construct() {
		$this->name        = __(
			'Post Content',
			CCTM_TXTDOMAIN
		);
		$this->description = __(
			"Show a post's content inside of a widget.",
			CCTM_TXTDOMAIN
		);
		$widget_options    = array(
			'classname'   => __CLASS__,
			'description' => $this->description,
		);

		parent::__construct( __CLASS__, $this->name, $widget_options, $this->control_options );

		// We only need the additional functionality for the back-end.
		// See http://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=331.
		// if( is_admin() && is_active_widget( false, false, $this->id_base, true )) {.
		if ( is_admin() && 'widgets.php' === substr( $_SERVER['SCRIPT_NAME'], strrpos( $_SERVER['SCRIPT_NAME'], '/' ) + 1 ) ) {
			wp_enqueue_script( 'thickbox' );
			wp_register_script( 'cctm_post_widget', CCTM_URL . '/js/post_widget.js', array( 'jquery', 'media-upload', 'thickbox' ), '1.12', false );
			wp_enqueue_script( 'cctm_post_widget' );
		}
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Create Form Elements
	 *
	 * Create only form elements.
	 *
	 * @param string $instance This instance.
	 */
	public function form( $instance ) {

		require_once CCTM_PATH . '/includes/class-get-posts-query.php';

		$formatted_post = ''; // Formatted post.

		if ( ! isset( $instance['title'] ) ) {
			$instance['title'] = ''; // Default value.
		}

		if ( isset( $instance['post_id'] ) && ! empty( $instance['post_id'] ) ) {
			$q                                = new GetPostsQuery();
			$post                             = $q->get_post( $instance['post_id'] );
			$tpl                              = CCTM::load_tpl( 'widgets/post_item.tpl' );
			$post['edit_selected_post_label'] = __(
				'Edit Selected Post',
				CCTM_TXTDOMAIN
			);
			$post['post_icon']                = CCTM::get_thumbnail( $instance['post_id'] );
			if ( 'attachment' === $post['post_type'] ) {
				$post['edit_url'] = get_admin_url( '', 'media.php' ) . "?attachment_id={$post['ID']}&action=edit";
			} else {
				$post['edit_url'] = get_admin_url( '', 'post.php' ) . "?post={$post['ID']}&action=edit";
			}

			$post['target_id'] = $this->get_field_id( 'target_id' );

			$formatted_post = CCTM::parse( $tpl, $post );
		} else {
			$instance['post_id'] = '';
		}

		if ( ! isset( $instance['formatting_string'] ) ) {
			$instance['formatting_string'] = '[+post_content+]';  // Default value.
		}

		if ( ! isset( $instance['post_type'] ) ) {
			$instance['post_type'] = 'post'; // Default value.
		}

		$post_types = get_post_types( array( 'public' => 1 ) );

		$post_type_options = '';

		foreach ( $post_types as $k => $v ) {
			$is_selected = '';
			if ( $k === $instance['post_type'] ) {
				$is_selected = ' selected="selected"';
			}

			$post_type_options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$k,
				$is_selected,
				$v
			);
		}

		$is_checked = '';
		if ( isset( $instance['override_title'] ) && 1 === $instance['override_title'] ) {
			$is_checked = ' checked="checked"';
		}

		print '
			<p>'
			. $this->description
				. '<a href="http://code.google.com/p/wordpress-custom-content-type-manager/wiki/Post_Widget">
					<img src="' . CCTM_URL . '/images/question-mark.gif" width="16" height="16" />
				</a>
			</p>
			<label class="cctm_label" for="' . $this->get_field_id( 'post_type' ) . '">Post Type</label>
			<input type="hidden" id="' . $this->get_field_id( 'post_id' ) . '" name="' . $this->get_field_name( 'post_id' ) . '" value="' . $instance['post_id'] . '" />
			<select name="' . $this->get_field_name( 'post_type' ) . '" id="' . $this->get_field_id( 'post_type' ) . '">'
				. $post_type_options
			. '	</select>
			<br />
			<br />
			<span class="button" onclick="javascript:select_post(\'' . $this->get_field_id( 'post_id' ) . '\',\'' . $this->get_field_id( 'target_id' ) . '\',\'' . $this->get_field_id( 'post_type' ) . '\');">'
				. __( 'Choose Post', CCTM_TXTDOMAIN ) . '
			</span>
			<br />
			<br />
			<strong>Selected Post</strong>
			<br />
			<!-- This is where we wrote the preview HTML -->
			<div id="' . $this->get_field_id( 'target_id' ) . '">' . $formatted_post . '</div>
			<!-- Thickbox ID -->
			<div id="thickbox_' . $this->get_field_id( 'target_id' ) . '"></div>
			<br />
			<br />
			<input type="checkbox" name="' . $this->get_field_name( 'override_title' ) . '" id="' . $this->get_field_id( 'override_title' ) . '" value="1" ' . $is_checked . '/>
			<label class="" for="' . $this->get_field_id( 'override_title' ) . '">'
				. __( 'Override Post Title', CCTM_TXTDOMAIN ) . '
			</label>
			<br />
			<br />
			<label class="cctm_label" for="' . $this->get_field_id( 'title' ) . '">'
				. __( 'Title', CCTM_TXTDOMAIN ) . '
			</label>
			<input type="text" name="' . $this->get_field_name( 'title' ) . '" id="' . $this->get_field_id( 'title' ) . '" value="' . $instance['title'] . '" />
			<label class="cctm_label" for="' . $this->get_field_id( 'formatting_string' ) . '">'
				. __( 'Formatting String', CCTM_TXTDOMAIN ) . '
			</label>
			<textarea name="' . $this->get_field_name( 'formatting_string' ) . '" id="' . $this->get_field_id( 'formatting_string' ) . '" rows="3" cols="30">'
				. $instance['formatting_string'] . '
			</textarea>';
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Widget
	 *
	 * Process the $args to something GetPostsQuery.
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance object.
	 */
	function widget( $args, $instance ) {

		// Avoid placing empty widgets.
		if ( ! isset( $instance['post_id'] ) || empty( $instance['post_id'] ) ) {
			return;
		}

		require_once CCTM_PATH . '/includes/class-get-posts-query.php';

		$post_id              = (int) $instance['post_id'];
		$q                    = new GetPostsQuery();
		$post                 = $q->get_post( $post_id );
		$post['post_content'] = do_shortcode( wpautop( $post['post_content'] ) );
		$output               = $args['before_widget'];

		if ( isset( $instance['override_title'] ) && 1 === $instance['override_title'] ) {
			$title = $instance['title'];
		} else {
			$title = $post['post_title']; // Default is to use the post's title.
		}

		if ( ! empty( $title ) ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		if ( ! empty( $instance['formatting_string'] ) ) {
			$output .= CCTM::parse( $instance['formatting_string'], $post );
		} else {
			$output .= $post['post_content'];
		}

		$output .= $args['after_widget'];

		print $output;
	}

	/**
	 * Register the Widgit
	 *
	 * Public static function that registers the widget.
	 */
	public static function register_this_widget() {
		register_widget( __CLASS__ );
	}

}

/*EOF*/
