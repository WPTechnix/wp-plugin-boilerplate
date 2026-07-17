# Testing

Two independent suites, each with its own bootstrap and PHPUnit config. For the quick
reference see [tests index](../tests/INDEX.md); for how to run them see
the [scripts index](../scripts/INDEX.md).

## Unit suite (Brain Monkey)

- Location: `tests/Unit/`, config `phpunit-unit.xml.dist`, bootstrap
  `tests/bootstrap/unit.php`.
- Test cases extend `Yoast\WPTestUtils\BrainMonkey\TestCase`.
- **No WordPress is loaded.** WordPress functions are mocked with
  [Brain Monkey](https://github.com/Brain-WP/BrainMonkey) — for example
  `Brain\Monkey\Actions\expectAdded('init')` asserts a hook was registered without a
  running WordPress.
- The bootstrap only loads the Composer autoloader, so these tests are fast and run
  anywhere: `./scripts/dev test:unit`.

Write unit tests for logic that can be isolated from WordPress. Mock the seams.

## Integration suite (WordPress test framework)

- Location: `tests/Integration/`, config `phpunit-integration.xml.dist`, bootstrap
  `tests/bootstrap/integration.php`.
- Test cases extend `Yoast\WPTestUtils\WPIntegration\TestCase`.
- Runs against a **real** WordPress install and MySQL database, so it can assert real
  behaviour (hooks actually firing, options persisting, queries running).

The bootstrap:

1. Loads the Composer autoloader.
2. Requires wp-test-utils' `WPIntegration/bootstrap-functions.php` explicitly — the
   package autoloads *classes* via classmap, but its bootstrap *functions* are not
   autoloaded.
3. Resolves the test-suite directory with
   `Yoast\WPTestUtils\WPIntegration\get_path_to_wp_test_dir()` (honouring
   `WP_TESTS_DIR`).
4. Registers the plugin to load on `muplugins_loaded`.
5. Calls `WPIntegration\bootstrap_it()`, which loads the PHPUnit Polyfills, then
   WordPress, then the MockObject autoloader — in that exact order.

## Where the test suite comes from

`scripts/install-wp-tests` downloads WordPress core and the PHPUnit test framework
(`tests/phpunit/{includes,data}` from `WordPress/wordpress-develop`) and writes a
`wp-tests-config.php`. It is idempotent: downloads are skipped when already present, and
the config is always rewritten so credentials stay current.

- **Locally**, the tools Docker image bakes the download in at build time (into `/opt`),
  so `./scripts/dev test:integration` is fast — only the database is created at run
  time by `test:setup`.
- **In CI**, `test.yml` runs the same script per matrix entry against a MySQL service,
  caching the downloaded suite by WordPress version.

`WP_TESTS_DIR` / `WP_CORE_DIR` point the bootstrap at the suite (baked image paths
locally; `/tmp` paths in CI).

## The PHPUnit 9.6 ceiling

`yoast/wp-test-utils` 1.x requires `phpunit-polyfills` ^1.1.5, which supports PHPUnit up
to 9.6. The polyfills 3.x/4.x lines (which support PHPUnit 11/12) are **not** compatible
with wp-test-utils 1.x. Keep `phpunit/phpunit` at `^9.6`. On PHP 8.4 this resolves to
9.6.35, which supports 8.4.

## Writing tests

### Conventions

| | Unit suite | Integration suite |
| --- | --- | --- |
| Directory | `tests/Unit/` | `tests/Integration/` |
| Namespace | `WPPluginBoilerplate\Tests\Unit` | `WPPluginBoilerplate\Tests\Integration` |
| Base class | `Yoast\WPTestUtils\BrainMonkey\TestCase` | `Yoast\WPTestUtils\WPIntegration\TestCase` |

- File name matches the class under test: `PluginTest.php` tests `Plugin`.
- Test classes are `final` and carry a `@covers \Fully\Qualified\ClassName` docblock.
- Test methods are named `test_descriptive_snake_case` and return `void`.
- File-level docblock uses `@package WPPluginBoilerplate\Tests`.
- Always `declare(strict_types=1)`.

### Example — unit test

```php
<?php
/**
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

  public function test_boot_registers_the_init_action(): void {
    Actions\expectAdded( 'init' )->once();

    ( new Plugin( '1.0.0' ) )->boot();
  }
}
```

### Example — integration test

```php
<?php
/**
 * @package WPPluginBoilerplate\Tests
 */

declare(strict_types=1);

namespace WPPluginBoilerplate\Tests\Integration;

use WPPluginBoilerplate\Plugin;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * @covers \WPPluginBoilerplate\Plugin
 */
final class PluginTest extends TestCase {

  public function test_boot_registers_an_init_hook(): void {
    $this->assertNotFalse(
      has_action( 'init', [ wp_plugin_boilerplate(), 'on_init' ] )
    );
  }
}
```

### When to use which

- **Unit tests** for isolated business logic: calculations, data transformations, value
  objects, service classes that accept dependencies. Mock every WordPress function call.
- **Integration tests** for behaviour that touches WordPress APIs: hooks, options,
  shortcodes, rewrite rules, REST endpoints, database queries.
