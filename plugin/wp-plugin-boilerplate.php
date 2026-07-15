<?php
/**
 * Plugin Name:       WP Plugin Boilerplate
 * Plugin URI:        https://github.com/wptechnix/wp-plugin-boilerplate
 * Description:       Batteries-included WordPress plugin boilerplate with Docker dev environment, coding standards, static analysis, testing, dependency scoping, and CI/CD.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            WPTechnix
 * Author URI:        https://wptechnix.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-plugin-boilerplate
 * Domain Path:       /languages
 */

declare(strict_types=1);
use WPPluginBoilerplate\Plugin;

defined( 'ABSPATH' ) || exit;

define( 'WP_PLUGIN_BOILERPLATE_VERSION', '0.1.0' );
define( 'WP_PLUGIN_BOILERPLATE_FILE', __FILE__ );
define( 'WP_PLUGIN_BOILERPLATE_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WP_PLUGIN_BOILERPLATE_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Load the plugin's autoloader.
 */
$autoload_candidates = [
	__DIR__ . '/vendor-prefixed/scoper-autoload.php',
	__DIR__ . '/vendor-prefixed/autoload.php',
];

foreach ( $autoload_candidates as $autoload_candidate ) {
	if ( is_file( $autoload_candidate ) ) {
		require_once $autoload_candidate;
		break;
	}
}

unset( $autoload_candidates, $autoload_candidate );

if ( ! class_exists( Plugin::class ) ) {
	return;
}

/**
 * Access the shared plugin instance.
 *
 * @since 0.1.0
 *
 * @return Plugin The shared plugin instance.
 */
function wp_plugin_boilerplate(): Plugin {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new Plugin( WP_PLUGIN_BOILERPLATE_VERSION );
	}

	return $plugin;
}

add_action(
	'plugins_loaded',
	static function (): void {
		wp_plugin_boilerplate()->boot();
	}
);
