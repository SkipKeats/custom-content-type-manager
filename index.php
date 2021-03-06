<?php
/**
 * Custom Content Type Manager: Advanced Custom Post Types
 *
 * @package     CustomContentTypeManager
 * @author      Everett Griffiths; GSA Data.gov Team
 * @copyright   Craftsman Coding et alia
 * @license     https://www.gnu.org/licenses/quick-guide-gplv3.html GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name: Custom Content Type Manager : Advanced Custom Post Types
 * Plugin URI: https://github.com/GSA/custom-content-type-manager
 *
 * Description: Allows users to create custom post types and custom fields,
 *              including dropdowns, checkboxes, and images. This gives WordPress
 *              CMS functionality making it easier to use WP for eCommerce or
 *              content-driven sites.
 *              Original plugin url: https://github.com/craftsmancoding/custom-content-type-manager
 *              Last CC version: 0.9.8.9
 * Version:     0.9.9.0
 * Author:      Everett Griffiths; GSA Data.gov Team
 * Author URI:  https://www.craftsmancoding.com/
 * Text Domain: custom-content-type-manager
 * Licence:     GPL-3.0+
 * Licence URI: https://www.gnu.org/licenses/quick-guide-gplv3.html
 */

/*
 * CONFIGURATION (for the developer):
 *
 * Define the names of functions and classes used by this plugin so we can test
 * for conflicts prior to loading the plugin and message the WP admins if there are
 * any conflicts.
 *
 * $function_names_used -- Add any functions declared by this plugin in the
 * main namespace (e.g. utility functions or theme functions).
 *
 * $class_names_used -- Add any class names that are declared by this plugin.
 *
 * Warning: the text-domain for the __() localization functions is hardcoded.
 */
$function_names_used = array(
	'get_custom_field',
	'get_custom_field_meta',
	'get_custom_field_def',
	'get_post_complete',
	'get_posts_sharing_custom_field_value',
	'get_relation',
	'get_unique_values_this_custom_field',
	'print_custom_field',
	'print_custom_field_meta',
	'uninstall_cctm',
);

$class_names_used = array(
	'CCTM',
	'Standardized_Custom_Fields',
	'CCTMtests',
	'CCTM_Form_Element',
	'CCTM_Ajax',
	'CCTM_Output_Filter',
	'CCTM_Pagination',
	'Summarize_Posts',
	'GetPostsQuery',
	'Get_Posts_Form',
	'SP_Post',
	'CCTM_Post_Type_Def',
	'CCTM_Import_Export',
);

// Not class constants: constants declared via define().
$constants_used = array(
	'CCTM_PATH',
	'CCTM_URL',
	'CCTM_3P_PATH',
	'CCTM_3P_URL',
);

// Used to store errors.
$error_items = '';

/**
 * Summary: Function runs if CCTM cannot load.
 * No point in localizing this, because we haven't loaded the textdomain yet.
 */
function custom_content_type_manager_cannot_load() {
	global $error_items;
	print '<div id="custom-post-type-manager-warning" class="error fade"><p><strong>'
		. 'The Custom Post Type Manager plugin cannot load correctly!'
		. '</strong> '
		. 'Another plugin has declared conflicting class, function, or constant names:'
		. '<ul style="margin-left:30px;">' . $error_items . '</ul>'
		. '</p>'
		. '<p>You must deactivate the plugins that are using these conflicting names.</p>'
		. '<p>If you have the Summarize_Posts plugin installed, deactivate it now: it is already included in the CCTM.</p>'
		. '</div>';
}

/**
 * Summary: Run on plugin activation or on demand.  This will populate
 * CCTM::$errors if errors are encountered.
 */
function cctm_run_tests() {
	require_once 'includes/class-cctm.php';
	require_once 'includes/constants.php';
	require_once 'tests/CCTMtests.php';
	CCTMtests::run_tests();
}

/**
 * The following code tests whether or not this plugin can be safely loaded.
 * If there are no conflicts, the loader.php is included and the plugin is loaded,
 * otherwise, an error is displayed in the manager.
 */
// Check for conflicting function names.
foreach ( $function_names_used as $f_name ) {
	if ( function_exists( $f_name ) ) {
		/* Translators: This refers to a PHP function e.g. my_function() { ... } */
		$error_items .= sprintf(
			'<li>%1$s: %2$s</li>',
			__(
				'Function',
				'custom-content-type-mgr'
			),
			$f_name
		);
	}
}

// Check for conflicting Class names.
foreach ( $class_names_used as $cl_name ) {
	if ( class_exists( $cl_name ) ) {
		/* Translators: This refers to a PHP class e.g. class MyClass { ... } */
		$error_items .= sprintf(
			'<li>%1$s: %2$s</li>',
			__(
				'Class',
				'custom-content-type-mgr'
			),
			$f_name
		);
	}
}

// Check for conflicting Constants.
foreach ( $constants_used as $c_name ) {
	if ( defined( $c_name ) ) {
		/* Translators: This refers to a PHP constant as defined by the define() function */
		$error_items .= sprintf(
			'<li>%1$s: %2$s</li>',
			__(
				'Constant',
				'custom-content-type-mgr'
			),
			$f_name
		);
	}
}

// Check stuff when the plugin is activated.
register_activation_hook( __FILE__, 'cctm_run_tests' );

// Fire the error, or load the plugin.
if ( $error_items ) {
	$error_items = '<ul>' . $error_items . '</ul>';
	add_action( 'admin_notices', 'custom_content_type_manager_cannot_load' );
} else { // CLEARED FOR LAUNCH!!! ---> Load the plugin.
	require_once 'loader.php';
}

/*EOF*/
