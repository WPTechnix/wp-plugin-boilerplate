# Architecture

How the repository is organised, how the plugin loads its code, and how dependencies
are isolated for release. For the day-to-day command reference see the [scripts index](../scripts/INDEX.md).

## The two-tree split

The repository is deliberately split into "what ships" and "what does not":

- **`plugin/`** is the only thing a release contains. Its `composer.json` declares the
  plugin's **runtime** dependencies and nothing else.
- **The root** holds development tooling — the test suites, PHPCS/PHPStan configs, the
  Docker environment, CI, and a root `composer.json` whose `require-dev` installs all
  of it. The root autoloader also maps the plugin's classes
  (`WPPluginBoilerplate\ => plugin/src/`) so tests and analysis can find them without
  the plugin's own `vendor/` existing.

This is why there are two `composer.json` files. During development you install the
**root** one (`./scripts/dev composer install`); the **plugin** one is only installed
as part of scoping/release.

## Autoloading

The plugin **always** loads its code from `plugin/vendor-prefixed/` — the tree
`scripts/scope` builds — so what runs locally is exactly what ships. The main plugin
file (`plugin/wp-plugin-boilerplate.php`) tries, in order of specificity:

1. `plugin/vendor-prefixed/scoper-autoload.php` — a scoped build with exposed symbols
   (php-scoper writes this file into the output directory when there are any).
2. `plugin/vendor-prefixed/autoload.php` — a scoped build without exposed symbols, or
   (in the no-dependency case) Composer's own autoloader moved there unchanged.

There is deliberately **no** fallback to an unscoped `plugin/vendor/autoload.php`: the
plugin never assumes an unscoped tree, not even during development.

If neither file exists — which is the case when you run the plugin straight from a
development checkout that has not run `scripts/scope` — the parent (root) Composer
autoloader has already registered the plugin's classes, so the bootstrap proceeds
anyway. The check is `class_exists( Plugin::class )`, not "does a file exist", precisely
so a development checkout (and the test suites) boot correctly.

## Dependency scoping

WordPress loads every active plugin into one PHP process. If two plugins each bundle,
say, Guzzle at different versions, whichever loads first wins and the other breaks.
[php-scoper](https://github.com/humbug/php-scoper) prevents this by rewriting the
plugin's dependencies into a private namespace (`WPPluginBoilerplate_Deps`, configured
in `scoper.inc.php`).

`scripts/scope` runs the pipeline, and it always produces `plugin/vendor-prefixed/`:

1. `composer update --no-dev --optimize-autoloader` inside `plugin/` to materialise
   `plugin/vendor/` from the current `plugin/composer.json`. (`update`, not `install`:
   `plugin/composer.lock` is a gitignored build artifact regenerated every run, so a
   stale lock can never break the build.)
2. If there are **no** third-party packages (the default — a plugin with no runtime
   dependencies), there is nothing to prefix: Composer's own autoloader — which already
   maps the plugin's `WPPluginBoilerplate\` namespace — is simply moved to
   `plugin/vendor-prefixed/`. Composer's paths are `__DIR__`-relative, so it keeps
   working after the move.
3. Otherwise run `php-scoper add-prefix` scanning `plugin/vendor/` into
   `plugin/vendor-prefixed/`, then regenerate an optimized autoloader over the prefixed
   tree with `composer dump-autoload` (PSR-4 stays available for new plugin classes),
   and delete the unscoped `plugin/vendor/`.

Either way the result is the same shape — `plugin/vendor-prefixed/autoload.php` (plus
`scoper-autoload.php` when there are exposed symbols) — which is exactly what the main
plugin file loads.

### Adding a runtime dependency

Add it to `plugin/composer.json` (not the root), then rebuild the scoped tree. The
`./scripts/dev plugin <command>` shortcut runs Composer against `plugin/composer.json`
and re-runs `scripts/scope` automatically after `require`/`remove`/`install`/`update`,
so `plugin/vendor-prefixed/` stays in sync:

```
./scripts/dev plugin require guzzlehttp/guzzle   # adds it and re-scopes in one step
```

### Why the finder scans only `plugin/vendor`

php-scoper strips the *longest common base path* shared by every scanned file and
appends the remainder to the output directory. Scanning a single root (`plugin/vendor`)
makes that base `plugin/vendor`, so files land directly under `vendor-prefixed/`.
Adding another path (for example `plugin/composer.json`) would raise the common base to
`plugin/` and nest the entire output one level too deep. `scoper.inc.php` therefore
scans only `plugin/vendor`, guarded by an `is_dir()` check so the config is valid even
before that directory exists.

### Why php-scoper is a PHAR, not a Composer dependency

php-scoper pulls in a specific `nikic/php-parser` version that conflicts with the one
PHPStan uses. Installing both in the same tree breaks static analysis, so php-scoper is
consumed as a pinned PHAR (baked into the tools Docker image and downloaded in the
release workflow) and kept out of `composer.json`.

## Development vs release, side by side

| | Development checkout | Released zip (no runtime deps) | Released zip (with runtime deps) |
| --- | --- | --- | --- |
| Autoloader | root `vendor/autoload.php` | `plugin/vendor-prefixed/autoload.php` | `plugin/vendor-prefixed/autoload.php` |
| Dependencies | none in `plugin/` (root dev tree only) | none | scoped under `WPPluginBoilerplate_Deps` |
| Composer manifests | present | stripped from zip | stripped from zip |
