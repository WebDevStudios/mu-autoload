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
use ErrorException;

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
			echo "Couldn't include WP... guessing location\n";

			if ( ! self::wp_location_best_guess( $event->getComposer()->getPackage()->getExtra() ) ) {
				echo "mu-plugin autoloader installation aborted\n";
				exit( 1 );
			}
		}

		$vendor_dir = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		$base_path = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- OK, local only.
		file_put_contents(
			$base_path . '/mu-plugins/mu-autoload.php',
			self::get_autoloader_contents( self::get_wp_autoload_path( $vendor_dir ) )
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
		$dir = realpath( $dir );

		if ( '/' === $dir ) {
			return false;
		}

		$wp_load = $dir . '/wp-load.php';

		if ( is_readable( $wp_load ) ) {
			try {
				require_once $wp_load;
			} catch ( ErrorException $ee ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- empty OK.

				/*
				 * We may encounter a database exception if it's not hooked up,
				 * that's OK if we can still get to the define()s.
				 */
				if ( ! defined( 'WP_CONTENT_DIR' ) && ! defined( 'ABSPATH' ) ) {
					return false;
				}
			}
			return true;
		}

		return self::include_wp( $dir . '/..' );
	}

	/**
	 * Try to guess the WP install directory based on installer-paths in composer.json.
	 *
	 * @param array $extra Composer file extra section.
	 * @return boolean True if location guessed and path define()s set, false otherwise.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2020-02-04
	 */
	private static function wp_location_best_guess( array $extra ) {
		if ( empty( $extra['installer-paths'] ) ) {
			return false;
		}

		$composer_json_dir = getcwd();

		foreach ( $extra['installer-paths'] as $path => $constraints ) {
			$value = ( is_array( $constraints ) && count( $constraints ) === 1 ) ? current( $constraints ) : $constraints;

			if ( in_array( $value, array( 'type:wordpress-muplugin', 'type:wordpress-plugin' ), true ) ) {
				$parts = explode( '/', $path );

				foreach ( $parts as $index => $dir ) {
					if ( in_array( $dir, array( 'wp-content', 'mu-plugins', 'plugins' ), true ) ) {

						$extra_path = 'wp-content' !== $dir ? '/..' : '';
						$partial    = array_slice( $parts, 0, $index + 1 );
						$wp_content = realpath( $composer_json_dir . '/' . join( '/', $partial ) . $extra_path );

						define( 'WP_CONTENT_DIR', $wp_content );
						define( 'ABSPATH', realpath( $wp_content . '/..' ) );

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Try to do some WP constant substitutions in the autoload directory path.
	 *
	 * @param string $vendor_dir Vendor directory.
	 * @return string Autoload file path, quoted for include, with possible WP constant substitutions.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function get_wp_autoload_path( $vendor_dir ) {
		if ( defined( 'WP_CONTENT_DIR' ) && 0 === strpos( $vendor_dir, WP_CONTENT_DIR ) ) {
			return "WP_CONTENT_DIR . '" . substr( $vendor_dir, strlen( WP_CONTENT_DIR ) ) . "/autoload.php'";
		} elseif ( defined( 'ABSPATH' ) && 0 === strpos( $vendor_dir, ABSPATH ) ) {
			return "ABSPATH . '" . substr( $vendor_dir, strlen( ABSPATH ) ) . "/autoload.php'";
		}
		return "'{$vendor_dir}/autoload.php'";
	}

	/**
	 * Get the autoloader file contents.
	 *
	 * @param string $autoload_path Path to autoload.php.
	 * @return string PHP autoloader file contents.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function get_autoloader_contents( $autoload_path ) {
		$date = date( 'Y-m-d' );
		return <<<LOADER
<?php
/**
 * Autoload classes required by the project.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  {$date}
 * @package WebDevStudios\MUAutoload
 */

\$autoload = {$autoload_path};

if ( is_readable( \$autoload ) ) {
	require_once \$autoload;
}


LOADER;
	}
}
