<?php
/**
 * PHPUnit bootstrap for the WordPress integration test suite.
 *
 * Boots the WordPress PHPUnit test framework against a real database and loads the
 * plugin as a must-use plugin so its hooks run. The heavy lifting (loading the
 * PHPUnit Polyfills before WordPress, then the WP bootstrap, then the MockObject
 * autoloader) is delegated to Yoast\WPTestUtils\WPIntegration so the load order is
 * always correct.
 *
 * WP_TESTS_DIR must point at a prepared test suite. Locally the tools Docker image
 * bakes it into /opt and `./scripts/dev test:setup` creates the database; in CI
 * scripts/install-wp-tests prepares it per matrix run.
 *
 * @package WPPluginBoilerplate\Tests
 */

declare(strict_types=1);

use Yoast\WPTestUtils\WPIntegration;

$autoload = dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! is_file( $autoload ) ) {
	fwrite( STDERR, 'Run "./scripts/dev composer install" before running the tests.' . PHP_EOL );
	exit( 1 );
}

require_once $autoload;

// wp-test-utils autoloads classes via classmap, so its namespaced bootstrap functions
// (get_path_to_wp_test_dir(), bootstrap_it()) must be required explicitly.
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$tests_dir = WPIntegration\get_path_to_wp_test_dir();

if ( false === $tests_dir ) {
	fwrite( STDERR, 'Could not locate the WordPress test suite. Set WP_TESTS_DIR, or run' . PHP_EOL );
	fwrite( STDERR, '"./scripts/dev test:setup" (locally) or scripts/install-wp-tests (CI) first.' . PHP_EOL );
	exit( 1 );
}

// Give access to tests_add_filter() before WordPress loads.
require_once $tests_dir . 'includes/functions.php';

// Activate the plugin inside the test environment.
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__, 2 ) . '/plugin/wp-plugin-boilerplate.php';
	}
);

// Load the PHPUnit Polyfills, WordPress, and the MockObject autoloader in the right order.
WPIntegration\bootstrap_it();
