<?php
/**
 * CCTM Import Export Class file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Import_Export
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */

/**
 * CCTM Import Export Class
 *
 * Used to handle various tasks involved with the importing and exporting of CCTM definition data.
 * They all live here because these functions otherwise don't see any action.
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Import_Export
 * @author Everett Griffiths and others
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.9.9.0
 */
class CCTM_Import_Export {

	/**
	 * API for dedicated CCTM pastebin user.
	 */
	const PASTEBIN_DEV_KEY  = '';
	const PASTEBIN_ENDPOINT = '';
	const EXTENSION         = '.cctm.json';

	/**
	 * List any custom field types that can store a reference to a post ID. When exporting, we
	 * need to remove any default values because the post IDs from one site will not transfer
	 * to another.
	 *
	 * @var array $referential_field_types Store references to post IDs.
	 */
	public static $referential_field_types = array( 'image', 'relation', 'media' );

	// ----------------------------------------------------------------------------!
	// Public Functions!
	// ----------------------------------------------------------------------------!
	/**
	 * Takes a definition file and activates it by copying it into the current
	 * CCTM::$data structure.
	 *
	 * @param string $filename name of definition file, not including 'wp-content/uploads/cctm/defs/'.
	 * @return boolean (true on success, false on fail).
	 */
	public static function activate_def( $filename ) {
		$upload_dir = wp_upload_dir();
		if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
			CCTM::$errors['json_decode_error'] = __(
				'WordPress issued the following error: ',
				CCTM_TXTDOMAIN
			) . $upload_dir['error'];
			return false;
		}

		$dir = $upload_dir['basedir'] . '/' . CCTM::base_storage_dir . '/' . CCTM::def_dir . '/';

		$data = self::load_def_file( $dir . $filename );

		// Check for errors.
		if ( ! empty( CCTM::$errors ) ) {
			return false;
		}

		// Merge the data.
		CCTM::$data['post_type_defs']    = $data['post_type_defs'];
		CCTM::$data['custom_field_defs'] = $data['custom_field_defs'];
		CCTM::$data['export_info']       = $data['export_info'];

		update_option( CCTM::db_key, CCTM::$data );

		return true;
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Equating CCTM Definition Data Structures?
	 *
	 * We can't just compare them because the menu_icon bits will be different: the candidate
	 * will have a relative URL, the live one will have an absolute URL.
	 *
	 * @param mixed $def1 CCTM definition data structure.
	 * @param mixed $def2 CCTM definition data structure.
	 * @return bool True if they are equal, false if not.
	 */
	public static function defs_are_equal( $def1, $def2 ) {
		if ( is_array( $def1 ) ) {
			foreach ( $def1 as $post_type => $def ) {
				if ( isset( $def1[ $post_type ]['menu_icon'] ) && ! empty( $def1[ $post_type ]['menu_icon'] ) ) {
					$def1[ $post_type ]['menu_icon'] = self::make_img_path_rel( $def1[ $post_type ]['menu_icon'] );
				}
			}
		}
		if ( is_array( $def2 ) ) {
			foreach ( $def2 as $post_type => $def ) {
				if ( isset( $def2[ $post_type ]['menu_icon'] ) && ! empty( $def2[ $post_type ]['menu_icon'] ) ) {
					$def2[ $post_type ]['menu_icon'] = self::make_img_path_rel( $def2[ $post_type ]['menu_icon'] );
				}
			}
		}

		if ( $def1 === $def2 ) {
			return true;
		} else {
			return false;
		}
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Deleting/Removing CCTM Definition Data Structures
	 *
	 * Removes data structures.
	 *
	 * @param array $defs filenames (not paths).
	 * @return bool true on success, false on failures (errors registered).
	 */
	public static function delete_defs( $defs ) {

		if ( empty( $defs ) ) {
			CCTM::$errors['no_definitions_defined'] = __(
				'Please specify at least one definition.',
				CCTM_TXTDOMAIN
			);
			return false;
		}

		$upload_dir = wp_upload_dir();
		if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
			CCTM::$errors['directory_does_not_exist'] = __(
				'WordPress issued the following error: ',
				CCTM_TXTDOMAIN
			) . $upload_dir['error'];
			return false;
		}

		foreach ( $defs as $d ) {

			$dir = $upload_dir['basedir'] . '/' . CCTM::base_storage_dir . '/' . CCTM::def_dir;

			$file = $dir . '/' . $d;

			// Being anal... no directory traversing allowed ( '..' or '/' ).
			if ( preg_match( '/\.\.|\//i', $d ) ) {
				CCTM::$errors['directory_traversing_not_allowed'] = __(
					'Directory traversing not allowed.',
					CCTM_TXTDOMAIN
				);
				return false;
			}

			if ( file_exists( $file ) ) {
				if ( ! @unlink( $file ) ) {
					CCTM::$errors['problems_deleting_file'] = sprintf(
						__(
							'Could not delete file: %s',
							CCTM_TXTDOMAIN
						),
						$file
					);
					return false;
				}
			} else {
				CCTM::$errors['file_does_not_exist'] = sprintf(
					__(
						'File does not exist: %s',
						CCTM_TXTDOMAIN
					),
					$file
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Export to Desktop
	 *
	 * Initiates a download: prints headers with payload
	 * or an error.
	 *
	 * @return Full download (with headers) or an error message.
	 */
	public static function export_to_desktop() {

		$save_me = self::get_payload_from_data( CCTM::$data );

		// Download-friendly name of the file.
		$download_title = self::get_download_title( CCTM::$data['export_info']['title'] );

		if ( $download = json_encode( $save_me ) ) {
			header( 'Content-Type: application/force-download' );
			header( 'Content-Disposition: attachment; filename=$download_title' );
			header( 'Content-length: ' . (string) mb_strlen( $download, '8bit' ) );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', mktime( date( 'H' ) + 2, date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) ) . ' GMT' );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			header( 'Pragma: no-cache' );
			print $download;
			exit;
		} else {
			print __(
				'There was a problem exporting your CCTM definition.',
				CCTM_TXTDOMAIN
			);
		}
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Export to Local Webserver
	 *
	 * Save the file to the local library or register an error.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function export_to_local_webserver() {
		$save_me = self::get_payload_from_data( CCTM::$data );

		// Download-friendly name of the file.
		$download_title = self::get_download_title( CCTM::$data['export_info']['title'] );

		// Where our library is...
		$upload_dir = wp_upload_dir();
		if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
			CCTM::$errors['directory_does_not_exist'] = __(
				'WordPress issued the following error: ',
				CCTM_TXTDOMAIN
			) . $upload_dir['error'];
			return false;
		}
		$dir = $upload_dir['basedir'] . '/' . CCTM::base_storage_dir . '/' . CCTM::def_dir;

		$target_file = $dir . '/' . $download_title;

		if ( file_exists( $target_file ) ) {
			CCTM::$errors['file_exists'] = sprintf(
				__(
					'A file named %s already exists in the definition library.  Please choose another title.',
					CCTM_TXTDOMAIN
				),
				"<code>$download_title</code>"
			);
			return false;
		}

		$payload = json_encode( $save_me );

		$fh = @fopen( $target_file, 'w' );
		if ( $fh ) {
			fwrite( $fh, $payload );
			fclose( $fh );
			return true;
		} else {
			CCTM::$errors['file_exists'] = sprintf(
				__(
					'An error was encountered while trying to write to the definition library directory (%s). Please check the file permissions on your server.',
					CCTM_TXTDOMAIN
				),
				"<code>$dir</code>"
			);
			return false;
		}
	}

	/**
	 * Export to Pastebin
	 *
	 * FUTURE: see http://pastebin.com/api
	 */
	public static function export_to_pastebin() {

	}

	// -----------------------------------------------------------------------------!
	/**
	 * Get Definitions
	 *
	 * Load up available defs, i.e. any .json file inside the wp-content/uploads/cctm/defs/
	 *
	 * @return mixed Array of filenames (no path included).
	 */
	public static function get_defs() {
		$available_defs = array();

		$upload_dir = wp_upload_dir();
		if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
			CCTM::$errors['json_decode_error'] = __(
				'WordPress issued the following error: ',
				CCTM_TXTDOMAIN
			) . $upload_dir['error'];
			return $available_defs;
		}

		$dir = $upload_dir['basedir'] . '/' . CCTM::base_storage_dir . '/' . CCTM::def_dir . '/';

		$handle = opendir( $dir );
		if ( $handle ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				// Some files look like "your_def.cctm (1).json".
				if ( ! preg_match( '/^\./', $file ) && preg_match( '/.json$/i', $file ) ) {
					$available_defs[] = $file;
				}
			}
			closedir( $handle );
		}

		return $available_defs;
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Get Download Title
	 *
	 * Convert a human title into one we can use for a downloadable file:
	 * i.e. one without spaces or weird characters and a ".cctm.json" EXTENSION.
	 * Default output here is 'definition.cctm.json'
	 *
	 * @param string $title e.g. 'Books, Movies, and Plots'.
	 * @return string e.g. 'books_movies_and_plots.cctm.json'.
	 */
	public static function get_download_title( $title ) {
		if ( ! empty( $title ) ) {
			$title = strtolower( $title );
			$title = preg_replace( '/\s+/', '_', $title );
			$title = preg_replace( '/[^a-z_0-9]/', '', $title );
		} else {
			$title = 'definition'; // Default basename.
		}

		return $title . '.cctm.json';
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Get Payload from Data
	 *
	 * Convert the CCTM self::$data (i.e. THE data) to the data structure we use in
	 * an export file.
	 *
	 * @param mixed $data from CCTM::$data.
	 * @return mixed Groomed data with some tracking info appended
	 */
	public static function get_payload_from_data( $data ) {

		$payload = array();

		// Grab the important stuff.
		$payload['export_info']       = CCTM::get_value( $data, 'export_info' );
		$payload['post_type_defs']    = CCTM::get_value( $data, 'post_type_defs', array() );
		$payload['custom_field_defs'] = CCTM::get_value( $data, 'custom_field_defs', array() );

		// 1. Filter out absolute paths used for menu icons (they won't translate to a new server).
		foreach ( $payload['post_type_defs'] as $post_type => $def ) {
			if ( isset( $payload['post_type_defs'][ $post_type ]['menu_icon'] ) && ! empty( $payload['post_type_defs'][ $post_type ]['menu_icon'] ) ) {
				$payload['post_type_defs'][ $post_type ]['menu_icon'] = self::make_img_path_rel( $payload['post_type_defs'][ $post_type ]['menu_icon'] );
			}
		}

		// 2. Zero out any default values for referential fields (any other site won't have the same post IDs to support them).
		// See http://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=66.
		foreach ( $payload['custom_field_defs'] as $field => $field_def ) {
			if ( in_array( $field_def['type'], self::$referential_field_types, true ) ) {
				$payload['custom_field_defs'][ $field ]['default_value'] = '';
			}
		}

		// Append additional tracking info.
		// Consider user data: http://codex.wordpress.org/get_currentuserinfo.
		$payload['export_info']['_timestamp_export'] = time();
		$payload['export_info']['_source_site']      = site_url();
		$payload['export_info']['_charset']          = get_bloginfo( 'charset' );
		$payload['export_info']['_language']         = get_bloginfo( 'language' );
		$payload['export_info']['_wp_version']       = get_bloginfo( 'version' );
		$payload['export_info']['_cctm_version']     = CCTM::get_current_version();

		return $payload;
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Get WP Subdirectory
	 *
	 * Tests whether WP is installed in a sub directory or not.  If WP is installed
	 * in a sub directory, this will return it, e.g. 'blog', otherwise returns
	 * an empty string.
	 *
	 * @return string
	 */
	public static function get_subdir() {
		$info = parse_url( site_url() );
		if ( isset( $info['path'] ) ) {
			return $info['path'];
		}
		return '';
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Is the Basename Valid?
	 *
	 * Used to check the names of uploaded files -- also passed in URLs
	 * Basename only! Not full path!
	 *
	 * @param string $basename file basename, e.g. 'my_def.cctm.json'.
	 * @return bool False if it's a bad filename, true if it's legit.
	 */
	public static function is_valid_basename( $basename ) {
		if ( empty( $basename ) ) {
			return false;
		}

		// Must have the .cctm.json extension.
		if ( ! preg_match( '/' . self::EXTENSION . '$/i', $basename ) ) {
			return false;
		}
		$cnt;
		$basename = str_replace( self::EXTENSION, '', $basename, $cnt );
		if ( preg_match( '/[^a-z_\-0-9]/i', $basename ) ) {
			return false;
		}
		// I guess the filename is legit.
		return true;
	}

	/**
	 * Is the Definition Structure Valid?
	 *
	 * Given an array, we make sure it's a valid for use as a CCTM definition.
	 * This checks the validity of a 0.9.4 version of the data structure.
	 * .cctm.json files from older versions aren't compatible.
	 *
	 * @param array $data mixed data structure.
	 * @return bool True if the structure is valid.
	 */
	public static function is_valid_def_structure( $data ) {

		if ( ! is_array( $data ) ) {
			return false;
		}

		// Check the required keys.
		if (
			! isset( $data['post_type_defs'] )
			|| ! isset( $data['custom_field_defs'] )
			|| ! isset( $data['export_info'] )
		) {
			return false;
		}

		// And a bit of the stamping stuff...
		if ( ! isset( $data['export_info']['_timestamp_export'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_source_site'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_charset'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_language'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_wp_version'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_cctm_version'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['title'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['author'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['url'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['template_url'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['description'] ) ) {
			return false;
		}

		// If we make it here, it's a thumbs-up.
		return true;
	}

	/**
	 * Is the Upload Structure Valid?
	 *
	 * Given an array, we make sure it's a valid import package
	 *
	 * @param array $data mixed data structure.
	 * @return bool True if the structure is valid.
	 */
	public static function is_valid_upload_structure( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		} elseif ( ! isset( $data['export_info'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_timestamp_export'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_source_site'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_charset'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_language'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_wp_version'] ) ) {
			return false;
		} elseif ( ! isset( $data['export_info']['_cctm_version'] ) ) {
			return false;
		} elseif ( ! isset( $data['payload'] ) ) {
			return false;
		}

		return self::is_valid_def_structure( $data['payload'] );
	}

	/**
	 * Import from Preview
	 *
	 * The preview data object is stored nextdoor in a neighboring option.
	 */
	public static function import_from_preview() {

		$settings  = get_option( CCTM::db_key_settings, array() );
		$candidate = CCTM::get_value( $settings, 'candidate' );
		$new_data  = CCTM::get_value( $candidate, 'payload' );

		// Clean up icon URLs: make them absolute again. See the ImportExport::export_to_desktop function
		// and issue 64:http://code.google.com/p/wordpress-custom-content-type-manager/issues/detail?id=64.
		foreach ( $new_data as $post_type => $def ) {
			if ( isset( $new_data[ $post_type ]['menu_icon'] ) && ! empty( $new_data[ $post_type ]['menu_icon'] ) ) {
				$new_data[ $post_type ]['menu_icon'] = self::make_img_path_abs( $new_data[ $post_type ]['menu_icon'] );
			}
		}
		update_option( CCTM::db_key, $new_data );
	}

	/**
	 * Import from Pastebin.
	 */
	public static function import_from_pastebin() {}

	// -----------------------------------------------------------------------------!
	/**
	 * Load Definition file
	 *
	 * Given a filename of a def file, load up the file and convert it to a data
	 * structure.  Errors are set if there are problems (CCTM::$errors).
	 *
	 * @param string $filename filename (including path).
	 * @return mixed
	 */
	public static function load_def_file( $filename ) {

		if ( ! file_exists( $filename ) ) {
			CCTM::$errors['definition_not_found'] = sprintf(
				__(
					'The definition file could not be found: %s',
					CCTM_TXTDOMAIN
				),
				"<code>$filename</code>"
			);
			return array();
		}
		$def_str = file_get_contents( $filename );
		$def     = json_decode( $def_str, true );
		if ( ! $def ) {
			CCTM::$errors['json_decode_error'] = sprintf(
				__(
					'There was a problem with the JSON decoding of the definition file: %s',
					CCTM_TXTDOMAIN
				),
				"<code>$filename</code>"
			);
			return array();
		}

		if ( ! self::is_valid_def_structure( $def ) ) {
			CCTM::$errors['definition_structure_invalid'] = sprintf(
				__(
					'The data structure in the file was not in the correct format: %s. This could be because the file is from an older version of the CCTM plugin.  See the <a href="http://code.google.com/p/wordpress-custom-content-type-manager/wiki/Import" target="_new">Wiki</a> for more information.',
					CCTM_TXTDOMAIN
				),
				"<code>$filename</code>"
			);
			return array();
		}

		// Correct for stripped image paths IF this is being installed on a site where WP is
		// installed in a sub dir.
		$subdir = self::get_subdir();
		if ( $subdir ) {
			foreach ( $def['post_type_defs'] as $post_type => &$d ) {
				if ( isset( $d['menu_icon'] ) ) {
					$d['menu_icon'] = '/' . $subdir . $d['menu_icon'];
				}
			}
		}

		return $def;
	}

	/**
	 * Make Image Path Absolute
	 *
	 * Make any relative image paths absolute on the new server. Image paths
	 * including a full url (e.g. "http://something...") will be ignored
	 * and returned unaltered.
	 *
	 * @see make_img_path_rel() for more info about the problem that this is solving.
	 *
	 * @param string $src URL representing an image.
	 * @return string
	 */
	public static function make_img_path_abs( $src ) {
		$parts = parse_url( $src );
		if ( isset( $parts['host'] ) ) {
			return $src; // <-- path is already absolute.
		} elseif ( ! isset( $parts['path'] ) ) {
			return $src; // Just in case the parse_url() fails.
		} else {
			// Here we manage the potential leading slash...
			$parts['path'] = preg_replace( '|^/?|', '', $parts['path'] );
			return site_url() . '/' . $parts['path'];
		}
	}

	/**
	 * Make Image Path Relative
	 *
	 * When storing image paths, esp. for the custom post type icons, the full URL
	 * is typically used, but if we are going to import and export definitions,
	 * that will break the definitions that use custom icons.  The solution is
	 * to strip the site url from the image path prior to export, then append it
	 * prior to import. This should allow images hosted on another domain to be
	 * used without being affected.
	 *
	 * This function should only act on images hosted locally on the same domain
	 * listed by the site_url();.
	 *
	 * @param string $src A full path to an image, e.g."http://x.com/my.jpg" OR "/sub/wp-content/plugins/custom-content-type-manager/images/icons/16x16/wizard.png".
	 * @return string A relative path to that image, e.g. "my.jpg".
	 */
	public static function make_img_path_rel( $src ) {
		// If left-most character is '/', then chop it and the subdir off.
		if ( '/' === substr( $src, 0, 1 ) ) {
			if ( self::get_subdir() ) {
				return str_replace( '/' . self::get_subdir(), '', $src );
			} else {
				return substr( $src, 1 ); // Chop off leading slash.
			}
		}
		return str_replace( site_url(), '', $src );
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Sanitize export Paramenters
	 *
	 * Sanitize posted data for a clean export. This just ensures that the user
	 * has entered some info about what they are about to export.
	 *
	 * @param mixed  $raw $_POST data.
	 * @param string $nonce_name (Optional) Name of nonce.
	 * @return mixed Sanitized post data.
	 */
	public static function sanitize_export_params( $raw, $nonce_name = '' ) {
		// Cleanup.
		unset( $raw[ $nonce_name ] );
		unset( $raw['_wp_http_referer'] );

		$sanitized = array();
		// Title.
		if ( empty( $raw['title'] ) ) {
			CCTM::$errors['title'] = __(
				'Title is required.',
				CCTM_TXTDOMAIN
			);
		} elseif ( preg_match( '/[^a-z\s\-\._0-9]/i', $raw['title'] ) ) {
			CCTM::$errors['title'] = __(
				'Only basic text characters are allowed for the title.',
				CCTM_TXTDOMAIN
			);
		} elseif ( strlen( $raw['title'] ) > 64 ) {
			CCTM::$errors['title'] = __(
				'The title cannot exceed 64 characters.',
				CCTM_TXTDOMAIN
			);
		}

		// Author.
		if ( empty( $raw['author'] ) ) {
			CCTM::$errors['author'] = __(
				'Author is required.',
				CCTM_TXTDOMAIN
			);
		} elseif ( preg_match( '/[^a-z\s\-_0-9\.@]/i', $raw['author'] ) ) {
			CCTM::$errors['author'] = __(
				'Only basic characters are allowed for the author field.',
				CCTM_TXTDOMAIN
			);
		} elseif ( strlen( $raw['author'] ) > 64 ) {
			CCTM::$errors['author'] = __(
				'The author name cannot exceed 32 characters.',
				CCTM_TXTDOMAIN
			);
		}

		// URL.
		if ( empty( $raw['url'] ) ) {
			$raw['url'] = site_url(); // Defaults to this site.
		} elseif ( ! preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $raw['url'] ) ) {
			CCTM::$errors['url'] = __(
				'The URL must be in a standard format, e.g. http://yoursite.com.',
				CCTM_TXTDOMAIN
			);
		} elseif ( strlen( $raw['url'] > 255 ) ) {
			CCTM::$errors['url'] = __(
				'The URL cannot exceed 255 characters.',
				CCTM_TXTDOMAIN
			);
		}

		// Template_url.
		if ( empty( $raw['template_url'] ) ) {
			$raw['template_url'] = ''; // Do nothing :-) .
		} elseif ( ! preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $raw['url'] ) ) {
			CCTM::$errors['template_url'] = __(
				'The template URL must be in a standard format, e.g. http://themeforest.com.',
				CCTM_TXTDOMAIN
			);
		} elseif ( strlen( $raw['template_url'] > 255 ) ) {
			CCTM::$errors['template_url'] = __(
				'The template URL cannot exceed 255 characters.',
				CCTM_TXTDOMAIN
			);
		}

		// Description.
		if ( empty( $raw['description'] ) ) {
			CCTM::$errors['description'] = __(
				'Description is required.',
				CCTM_TXTDOMAIN
			);
		} elseif ( strlen( $raw['description'] > 1024 ) ) {
			CCTM::$errors['description'] = __(
				'The description cannot exceed 1024 characters.',
				CCTM_TXTDOMAIN
			);
		}

		// HTML entities cleanup.
		$sanitized['title']        = htmlspecialchars( substr( preg_replace( '/[^a-z\s\-\._0-9]/i', '', trim( $raw['title'] ) ), 0, 64 ) );
		$sanitized['author']       = htmlspecialchars( substr( preg_replace( '/[^a-z\s\-_0-9\.@]/i', '', trim( $raw['author'] ) ), 0, 64 ) );
		$sanitized['url']          = htmlspecialchars( substr( trim( $raw['url'] ), 0, 255 ) );
		$sanitized['template_url'] = htmlspecialchars( substr( trim( $raw['template_url'] ), 0, 255 ) );
		$sanitized['description']  = htmlspecialchars( substr( strip_tags( trim( $raw['description'] ) ), 0, 1024 ) );

		return $sanitized;
	}

	/**
	 * Validate Data Structures
	 *
	 * Take a data structure and return true or false as to whether or not it's
	 * in the correct format for a CCTM definition.
	 *
	 * @param array $data Data structure array.
	 */
	public static function validate_data_structure( $data ) {
		// Move portions from CCTM::_sanitize_import_params.
	}

}

/*EOF*/
