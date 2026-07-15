<?php
/**
 * Main plugin class.
 */

declare(strict_types=1);

namespace WPPluginBoilerplate;

/**
 * Bootstraps the plugin by wiring its hooks.
 *
 * This is an intentionally small example so the boilerplate ships with something the
 * unit and integration test suites can exercise. Replace it with your plugin's real
 * bootstrap.
 *
 * @since 0.1.0
 */
final class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $version The plugin version.
	 */
	public function __construct( string $version ) {
		$this->version = $version;
	}

	/**
	 * Register the plugin's WordPress hooks.
	 *
	 * @since 0.1.0
	 */
	public function boot(): void {
		add_action( 'init', [ $this, 'on_init' ] );
	}

	/**
	 * Fire the plugin's own init hook once WordPress has initialised.
	 *
	 * @since 0.1.0
	 */
	public function on_init(): void {
		/**
		 * Fires when the plugin has initialised.
		 *
		 * @since 0.1.0
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'wp_plugin_boilerplate_init', $this );
	}

	/**
	 * Get the plugin version.
	 *
	 * @since 0.1.0
	 */
	public function version(): string {
		return $this->version;
	}
}
