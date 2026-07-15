# CI & release workflows

All workflows run natively (no Docker) via `shivammathur/setup-php`, which is faster and
cleaner for matrices than building the dev image per combination. The environment-
agnostic scripts (`scripts/scope`, `scripts/install-wp-tests`) are the shared seam
between local Docker runs and CI. Every workflow declares least-privilege `permissions`
and cancels superseded runs via `concurrency`.

## `lint.yml`

Triggers on pull requests and pushes to `main`. `permissions: contents: read`.

- **coding-standards** — PHP 8.3, `composer install`, then PHPCS and PHPStan. PHPCS
  runs with `--report=checkstyle` piped through `cs2pr`, and PHPStan with
  `--error-format=github`, so violations show up as inline annotations on the PR.
- **editorconfig** — sets up editorconfig-checker and runs it with
  `-disable-max-line-length` (line length is owned by PHPCS, which understands PHP
  context). There is no local config file; any excludes/toggles are passed as CLI flags
  here.

## `test.yml`

Triggers on pull requests and pushes to `main`. `permissions: contents: read`.

- **unit** — matrix PHP `8.0, 8.1, 8.2, 8.3, 8.4`; runs the unit config. No database.
- **integration** — matrix PHP `8.0–8.4` × WordPress `latest`, plus a previous
  WordPress release and a `nightly` entry that is allowed to fail
  (`continue-on-error`). A `mysql:8.4` service backs it; `scripts/install-wp-tests`
  prepares the suite (cached by WordPress version) and the integration config runs.

To test more WordPress versions, add entries to the `integration` matrix `include:`
list.

## `release.yml`

Triggered by pushing a tag matching `v*`. `permissions: contents: write` (needed only to
create the release and upload the asset). Steps:

1. **Checkout** with full history and no persisted credentials.
2. **Parse tag** — strips the leading `v` to get the full version and the base version
   (any pre-release suffix after `-` is stripped, e.g. `v1.2.3-beta.1` → version
   `1.2.3-beta.1`, base `1.2.3`). A hyphen in the version sets `prerelease=true`.
3. **Locate main plugin file** — finds the `*.php` in `plugin/` carrying the
   `Plugin Name:` header and derives the slug from its filename, so a plugin renamed
   with `scripts/setup` still works.
4. **Verify version consistency** — the tag's base version (pre-release suffix stripped)
   must equal both the `Version:` header and the `*_VERSION` constant, or the build
   fails. This lets pre-release tags (e.g. `v1.2.3-rc.1`) match a stable plugin version
   (`1.2.3`) without bumping the plugin file back and forth.
5. **Generate POT** — `wptechnix/wp-pot-generator@v1` writes
   `plugin/languages/<slug>.pot`. Run before scoping so only first-party source is
   scanned.
6. **Setup PHP 8.3 + php-scoper** — php-scoper needs PHP ≥ 8.2; the shipped plugin still
   targets 8.0.
7. **Install root dependencies** — provides the `php-scoper-wordpress-excludes` package
   that `scoper.inc.php` reads, even when the plugin has no runtime dependencies of its
   own. (The Symfony Finder used by the config comes bundled inside the php-scoper PHAR,
   so it is not a project dependency.)
8. **Scope** — `scripts/scope` produces `plugin/vendor-prefixed/` (or is a no-op). This
   is the *same* script `./scripts/dev scope` runs locally, so the release is scoped
   exactly as development is — no CI-only scoping action that could drift from local.
9. **Stage & zip** — copies `plugin/` into `build/<slug>/`, dropping `composer.json`,
   `composer.lock`, `.gitkeep` and `node_modules/`, and zips it as
   `<slug>-<version>.zip` (a single top-level directory named for the slug, so
   WordPress extracts it correctly).
10. **Publish** — `softprops/action-gh-release@v3` attaches the zip, sets the
    pre-release flag, marks stable releases as "latest", and auto-generates release
    notes.

> Expression values (`steps.*.outputs.*`) are passed into the shell steps via `env:`
> rather than interpolated into the script body, so a crafted tag or path can't inject
> shell. Informational pipelines (`grep | head`, `unzip -l | head`) are guarded with
> `|| true` so `pipefail` + an early-closing `head` can't fail an otherwise-good build.

### Cutting a release

```
# 1. Bump the Version: header AND the *_VERSION constant in the main plugin file.
# 2. Commit on a branch, open a PR, merge to main.
# 3. Tag the merge commit and push:
git tag v1.2.3
git push origin v1.2.3
```

For a pre-release, tag with a hyphenated suffix — the plugin file stays at the stable
version (only the tag differs):

```
git tag v1.2.3-rc.1
git push origin v1.2.3-rc.1
```
