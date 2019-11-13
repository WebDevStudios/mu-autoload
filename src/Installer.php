<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- PSR4
/**
 * Installer for an mu-plugin autoloader.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  2019-11-12
 * @package WebDevStudios\MUAutoload
 */

namespace WebDevStudios\MUAutoload;

use Composer\Script\Event;

/**
 * Installer class.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  2019-11-12
 */
class Installer {

	/**
	 * Installer called by post-update-cmd composer script.
	 *
	 * @param Event $event Composer event.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	public static function install( Event $event ) {
		if ( ! self::include_wp( dirname( __FILE__ ) ) ) {
			echo "Couldn't include WP, mu-plugin autoloader installation aborted.";
			exit( 1 );
		}

		$vendor_dir = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- OK, local only.
		file_put_contents(
			WP_CONTENT_DIR . '/mu-plugins/mu-autoload.php',
			self::get_autoloader_contents( self::get_wp_dir( $vendor_dir ) )
		);
	}

	/**
	 * Recursively climb the directory tree looking for wp-load.php.
	 *
	 * @param string $dir Path to start at.
	 * @return boolean True if wp-load.php was included.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function include_wp( $dir ) {
		if ( '/' === realpath( $dir ) ) {
			return false;
		}

		$wp_load = $dir . '/wp-load.php';

		if ( is_readable( $wp_load ) ) {
			include $wp_load;
			return true;
		}

		return self::include_wp( $dir . '/..' );
	}

	/**
	 * Try to do some WP constant substitutions in the vendor directory path.
	 *
	 * @param string $vendor_dir Vendor directory.
	 * @return string Directory path with possible WP constant substitutions.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function get_wp_dir( $vendor_dir ) {
		if ( 0 === strpos( $vendor_dir, WP_CONTENT_DIR ) ) {
			return 'WP_CONTENT_DIR . ' . substr( $vendor_dir, strlen( WP_CONTENT_DIR ) );
		} elseif ( 0 === strpos( $vendor_dir, ABSPATH ) ) {
			return 'ABSPATH . ' . substr( $vendor_dir, strlen( ABSPATH ) );
		}
		return $vendor_dir;
	}

	/**
	 * Get the autoloader file contents.
	 *
	 * @param string $vendor_dir Vendor directory.
	 * @return string PHP autoloader file contents.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function get_autoloader_contents( $vendor_dir ) {
		$autoload = $vendor_dir . '/autoload.php';
		$date     = date( 'Y-m-d' );
		return <<<LOADER
<?php
/**
 * Autoload classes required by the project.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  {$date}
 * @package WebDevStudios\MUAutoload
 */

\$autoload = {$autoload};

if ( is_readable( \$autoload ) ) {
	require_once \$autoload;
}


LOADER;
	}
}
