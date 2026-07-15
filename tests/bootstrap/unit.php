<?php
/**
 * PHPUnit bootstrap for the Brain Monkey unit test suite.
 *
 * No WordPress is loaded here: unit tests mock WordPress with Brain Monkey through
 * Yoast\WPTestUtils\BrainMonkey\TestCase. This only needs the Composer autoloader,
 * which maps both the plugin classes (WPPluginBoilerplate\) and the test classes
 * (WPPluginBoilerplate\Tests\).
 *
 * @package WPPluginBoilerplate\Tests
 */

declare(strict_types=1);

$autoload = dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! is_file( $autoload ) ) {
	fwrite( STDERR, 'Run "./scripts/dev composer install" before running the tests.' . PHP_EOL );
	exit( 1 );
}

require_once $autoload;
