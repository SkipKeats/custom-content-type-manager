<?php
/**
 * CCTM Simple Image file
 *
 * PHP 7.2+
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Simple_Image
 * @author Simon Jarvis and others
 * @copyright 2006 Simon Jarvis
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link https://github.com/GSA/custom-content-type-manager
 * @since 0.8.0.0
 */

/**
 * CCTM Form Element Class
 *
 * This file was damn useful, so I adapted it from Mr. Jarvis. Thanks!
 * I'm using it to compensate for WordPress' erratic image resizing API.
 * Sorry, WP, but your API sucks.
 *
 * Copyright: 2006 Simon Jarvis
 * Date: 08/11/06
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @category Component
 * @package CCTM
 * @subpackage CCTM_Form_Element
 * @author Simon Jarvis and others
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 * @see http://www.white-hat-web-design.co.uk/blog/resizing-images-with-php/
 * @since 0.8.0.0
 */
class CCTM_Simple_Image {

	/**
	 * Image
	 *
	 * Image information.
	 *
	 * @var string $image Image name (probably).
	 */
	public $image;

	/**
	 * Image Type
	 *
	 * Type of image being resized.
	 *
	 * @var string $image_type Image type: png, jpg, etc.
	 */
	public $image_type;

	// -----------------------------------------------------------------------------!
	/**
	 * Load Image
	 *
	 * Full path to image, also takes a URL.
	 *
	 * @param string $filename Name of file.
	 */
	function load( $filename ) {

		$image_info       = getimagesize( $filename );
		$this->image_type = $image_info[2];

		if ( IMAGETYPE_JPEG === $this->image_type ) {
			$this->image = imagecreatefromjpeg( $filename );
		} elseif ( IMAGETYPE_GIF === $this->image_type ) {
			$this->image = imagecreatefromgif( $filename );
		} elseif ( IMAGETYPE_PNG === $this->image_type ) {
			$this->image = imagecreatefrompng( $filename );
		}
	}


	// -----------------------------------------------------------------------------!
	/**
	 * Save the Image
	 *
	 * Save the new image.
	 *
	 * @param string  $filename Full path to file.
	 * @param string  $image_type (optional) Image type: GIF, JPEG, or PNG.
	 * @param integer $compression (optional) Compression rate.
	 * @param string  $permissions (optional) passed to chmod, e.g. 775.
	 * @return bool TRUE on success or FALSE on failure.
	 */
	function save( $filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null ) {
		$success = '';
		if ( IMAGETYPE_JPEG === $image_type ) {
			$success = imagejpeg( $this->image, $filename, $compression );
		} elseif ( IMAGETYPE_GIF === $image_type ) {
			$success = imagegif( $this->image, $filename );
		} elseif ( IMAGETYPE_PNG === $image_type ) {
			$success = imagepng( $this->image, $filename );
		}

		if ( null !== $permissions ) {
			chmod( $filename, $permissions );
		}

		// Free memory
		// http://www.binarytides.com/blog/php-resize-large-images-with-imagemagick/.
		imagedestroy( $this->image );

		return $success;
	}

	// -----------------------------------------------------------------------------!
	/**
	 * Output
	 *
	 * The output from the conversion.
	 *
	 * @param string $image_type (optional).
	 */
	function output( $image_type = IMAGETYPE_JPEG ) {

		if ( IMAGETYPE_JPEG === $image_type ) {
			imagejpeg( $this->image );
		} elseif ( IMAGETYPE_GIF === $image_type ) {
			imagegif( $this->image );
		} elseif ( IMAGETYPE_PNG === $image_type ) {
			imagepng( $this->image );
		}
	}

	/**
	 * Get Image Width
	 *
	 * Get the image's width, in pixels
	 *
	 * @return integer
	 */
	function get_width() {
		return imagesx( $this->image );
	}

	/**
	 * Get Image Height
	 *
	 * Get the image's height, in pixels
	 *
	 * @return integer
	 */
	function get_height() {
		return imagesy( $this->image );
	}

	/**
	 * Resize Height
	 *
	 * Alter the image height to the new $height.
	 *
	 * @param integer $height Reset image height.
	 */
	function resize_to_height( $height ) {
		$height = (int) $height;
		$ratio  = $height / $this->get_height();
		$width  = $this->get_width() * $ratio;
		$this->resize( $width, $height );
	}


	/**
	 * Resize Width
	 *
	 * Resize the image width to the new $width.
	 *
	 * @param integer $width Reset image width.
	 */
	function resize_to_width( $width ) {
		$with   = (int) $width;
		$ratio  = $width / $this->get_width();
		$height = $this->getheight() * $ratio;
		$this->resize( $width, $height );
	}

	/**
	 * Scale the Image
	 *
	 * An integer 1 to 100.
	 *
	 * @param integer $scale Resize via scaling.
	 */
	function scale( $scale ) {
		$scale  = (int) $scale;
		$width  = $this->get_width() * $scale/100;
		$height = $this->getheight() * $scale/100;
		$this->resize( $width, $height );
	}


	/**
	 * Adjust Dimensions
	 *
	 * Adjust the image dimensions.
	 *
	 * @param integer $width Width.
	 * @param integer $height Height.
	 */
	function resize( $width, $height ) {
		$with      = (int) $width;
		$height    = (int) $height;
		$new_image = imagecreatetruecolor( $width, $height );

		if ( ! imagecopyresampled( $new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->get_width(), $this->get_height() ) ) {
			die( 'Resampling failed for ' . $new_image );
		}

		$this->image = $new_image;
	}
}

/*EOF*/
