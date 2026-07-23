<?php
/**
 * Integration tests for the Plugin class against a real WordPress install.
 *
 * @package WPPluginBoilerplate\Tests
 */

declare(strict_types=1);

namespace WPPluginBoilerplate\Tests\Integration;

use WPPluginBoilerplate\Plugin;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * @covers \WPPluginBoilerplate\Plugin
 */
final class Plugin_Test extends TestCase {

	/**
	 * The plugin's classes should be autoloadable inside WordPress.
	 *
	 * @return void
	 */
	public function test_plugin_class_is_available(): void {
		$this->assertTrue( class_exists( Plugin::class ) );
	}

	/**
	 * The global accessor should return the shared plugin instance.
	 *
	 * @return void
	 */
	public function test_accessor_returns_the_plugin_instance(): void {
		$this->assertInstanceOf( Plugin::class, wp_plugin_boilerplate() );
	}

	/**
	 * The plugin version should match the constant defined in the main file.
	 *
	 * @return void
	 */
	public function test_version_matches_the_plugin_constant(): void {
		$this->assertSame( WP_PLUGIN_BOILERPLATE_VERSION, wp_plugin_boilerplate()->version() );
	}

	/**
	 * boot() runs on plugins_loaded, so the init hook should be registered by now.
	 *
	 * @return void
	 */
	public function test_init_hook_is_registered(): void {
		$this->assertNotFalse(
			has_action( 'init', [ wp_plugin_boilerplate(), 'on_init' ] )
		);
	}
}
