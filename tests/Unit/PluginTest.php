<?php
/**
 * Unit tests for the Plugin class.
 *
 * @package WPPluginBoilerplate\Tests
 */

declare(strict_types=1);

namespace WPPluginBoilerplate\Tests\Unit;

use Brain\Monkey\Actions;
use WPPluginBoilerplate\Plugin;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * @covers \WPPluginBoilerplate\Plugin
 */
final class PluginTest extends TestCase {

	/**
	 * boot() should register the plugin's init action.
	 *
	 * @return void
	 */
	public function test_boot_registers_the_init_action(): void {
		Actions\expectAdded( 'init' )->once();

		( new Plugin( '1.0.0' ) )->boot();
	}

	/**
	 * version() should return the value passed to the constructor.
	 *
	 * @return void
	 */
	public function test_version_returns_the_configured_version(): void {
		$plugin = new Plugin( '2.5.0' );

		$this->assertSame( '2.5.0', $plugin->version() );
	}
}
