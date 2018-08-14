<?php
/**
 * Autoprefixer.
 *
 * @package   AutoPrefixer
 * @author    SEO Themes <info@seothemes.com>
 * @license   GPL-2.0+
 * @link      https://seothemes.com
 * @copyright 2017 SEO Themes
 *
 * Plugin Name:       Autoprefixer
 * Plugin URI:        https://seothemes.com
 * Description:       Automatically adds vendor prefixes to stylesheets.
 * Version:           1.0.0
 * Author:            SEO Themes
 * Author URI:        https://seothemes.com
 * Text Domain:       autoprefixer
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 */

namespace SEOThemes\Autoprefixer;

use Sabberworm\CSS\Parser;
use Sabberworm\CSS\CSSList\Document;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}


add_action( 'wp_print_styles', __NAMESPACE__ . '\remove_css', 100 );
/**
 * Remove all stylesheets and print custom.
 *
 * @since 1.0.0
 *
 * @return void
 */
function remove_css() {
	global $wp_styles;

	$wp_styles->queue = array();

	// TODO: Remove hardcoded stylesheet.
	echo '<link rel="stylesheet" id="prefixed" href="' . plugins_url( '/custom.css', __FILE__ ) . '">';
}

add_action( 'wp_print_styles', __NAMESPACE__ . '\merge_css' );
/**
 * Merge all stylesheets.
 *
 * @since 1.0.0
 *
 * @return void
 */
function merge_css() {
	global $wp_styles;

	foreach ( $wp_styles->queue as $handle ) {
		$styles_to_merge[] = $handle;
	}

	$ordered = [];

	foreach ( $styles_to_merge as $style ) {
		$key = array_search( $style, $wp_styles->queue );
		if ( false !== $key ) {
			$ordered[ $key ] = str_replace( WP_CONTENT_URL, untrailingslashit( WP_CONTENT_DIR ), $wp_styles->registered[ $style ]->src );
			unset( $wp_styles->queue[ $key ] );
			unset( $wp_styles->registered[ $style ] );
		}
	}
	ksort( $ordered );
	ob_start();
	foreach ( $ordered as $path ) {
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
	$concat = ob_get_clean();
	$file   = file_get_contents( __DIR__ . '/custom.css' );

	// Empty stylesheet for testing.
	file_put_contents( __DIR__ . '/custom.css', '' );

	if ( strpos( $file, $concat ) === false ) {

		$css      = $concat;
		$parser   = new Parser( $css );
		$tree     = $parser->parse();
		$prefixer = new Autoprefixer( $tree );
		$prefixer->prefix();

		file_put_contents( __DIR__ . '/custom.css', $tree->render() );

	} else {
		return;
	}

}
