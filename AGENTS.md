# AGENTS.md

Authoritative guide for humans and AI agents working on this plugin. Read it before
making changes. Deep-dive documents live in [`docs/`](docs/) and are linked from the
relevant sections below.

---

## 1. What this repository is

This repository holds the **WP Plugin Boilerplate** WordPress plugin (the code in
`plugin/` that ships to users), together with all the tooling used to develop, test,
lint and release it. That tooling comes ready to use:

- A Docker development environment (WordPress, MySQL, phpMyAdmin, Mailpit, WP-CLI).
- Coding standards (PHPCS via `wptechnix/wp-coding-standards`) and static analysis
  (PHPStan level 8 with WordPress stubs).
- Unit tests (Brain Monkey, no WordPress) and integration tests (the real WordPress
  test suite against a database), via Yoast's PHPUnit Polyfills and WP Test Utils.
- Dependency scoping with php-scoper so a release cannot collide with other plugins.
- GitHub Actions for linting, testing and tag-driven releases.

The repository is split in two:

- **`plugin/`** — the plugin that actually ships. Everything under it (and nothing
  else) goes into a release zip.
- **Everything else** — development tooling, tests, CI and configuration that never
  ships.

> New plugin from this template? Run `./scripts/dev setup` once to rename every
> placeholder (name, slug, namespace, constants) to your own. See §4.

---

## 2. Repository structure

```
.
├── plugin/                       # The distributable plugin (this is what ships)
│   ├── wp-plugin-boilerplate.php # Main file: headers, constants, autoloader, bootstrap
│   ├── uninstall.php             # Runs on uninstall (bare WP context, self-contained)
│   ├── src/                      # PSR-4 classes (WPPluginBoilerplate\ => src/)
│   ├── assets/                   # CSS/JS/images
│   ├── templates/                # View templates
│   └── composer.json             # The plugin's *runtime* dependencies only
│
├── tests/
│   ├── bootstrap/unit.php        # Brain Monkey bootstrap (no WordPress)
│   ├── bootstrap/integration.php # WordPress test-suite bootstrap (real DB)
│   ├── Unit/                     # Unit tests  (WPPluginBoilerplate\Tests\Unit\)
│   └── Integration/              # Integration tests (…\Tests\Integration\)
│
├── scripts/
│   ├── dev                       # The developer CLI (Docker front-end) — see [scripts/INDEX.md](scripts/INDEX.md)
│   ├── setup                     # One-time rename wizard (pure sh, no PHP needed)
│   ├── scope                     # Builds plugin/vendor-prefixed via php-scoper
│   ├── install-wp-tests          # Downloads/configures the WP test suite
│   ├── lib/runtime               # Shared Docker helpers (sourced by dev)
│   └── docker/                   # compose.yml, .env, Dockerfiles, mu-plugins
│
├── .github/workflows/            # lint.yml, test.yml, release.yml, commitlint.yml
│
├── composer.json                 # Dev tooling + the project autoloader (NOT shipped)
├── phpcs.xml.dist                # Coding-standard config
├── phpstan.neon.dist             # Static-analysis config
├── scoper.inc.php                # php-scoper config (prefix, excludes, finders)
├── phpunit-unit.xml.dist         # Unit test config
└── phpunit-integration.xml.dist  # Integration test config
```

Why two `composer.json` files: the **root** one installs dev tooling and provides the
autoloader used during development and tests. The **`plugin/`** one declares only the
plugin's runtime dependencies and is what gets scoped for a release. See
[architecture docs](docs/architecture.md).

All shared tool configs are committed as `*.dist` (`phpcs.xml.dist`,
`phpstan.neon.dist`, `phpunit-*.xml.dist`). Each tool auto-discovers the non-`.dist`
name first, so you can drop a local `phpcs.xml` / `phpstan.neon` / `phpunit-*.xml` to
override the shared config without touching version control; those bare names are
gitignored.

---

## 3. Prerequisites & execution contexts

**The only thing you need installed locally is Docker** (Desktop or Engine, with the
Compose v2 plugin). PHP, Composer, MySQL and WP-CLI all run inside containers. (The one
exception is `scripts/dev setup`, which is pure shell and runs on the bare host: it is
the very first step, before anything is installed.)

There are two execution contexts, sharing one script layer:

| Context | How PHP tooling runs | Entry point |
| --- | --- | --- |
| **Local development** | Inside Docker containers | `./scripts/dev …` |
| **CI (GitHub Actions)** | Natively via `shivammathur/setup-php` | the workflows |

`scripts/scope` and `scripts/install-wp-tests` are environment-agnostic (they assume
`php`/`composer`/`php-scoper` are already on `PATH`) so the *same* code runs in both
contexts. Only `scripts/dev` knows about Docker.

---

## 4. The `scripts/dev` CLI

All shell scripts are POSIX `sh`, extensionless, and work on any Linux distribution,
macOS, or Git Bash on Windows. See the [scripts index](scripts/INDEX.md) for the full
command reference, details on every sub-script (`setup`, `scope`, `install-wp-tests`),
and the Docker Compose environment.

### First-time setup

```
./scripts/dev setup                   # personalise this plugin (interactive)
./scripts/dev composer install        # install dev tooling
./scripts/dev plugin install          # install plugin runtime deps (auto-scopes)
./scripts/dev up                      # start the site
./scripts/dev test                    # confirm everything works
```

`scripts/setup` is an interactive wizard that walks you through every choice: plugin
display name, slug/text-domain, namespace, coding style (WordPress tabs or PSR-12), and
the local Docker host ports. It copies `scripts/docker/.env.example` to
`scripts/docker/.env`, rewrites every placeholder across the source, config, tooling and
docs, and renames the main plugin file to your slug. It needs an interactive terminal
and refuses to run twice unless given `--force`.

Every tooling command (`php`, `composer`, `phpcs`, etc.) spins up a fresh throwaway
container, adding ~100–200 ms per invocation. When running many commands in sequence,
use `shell tools` to open an interactive shell and run them directly:

```
./scripts/dev shell tools
/app $ composer install
/app $ vendor/bin/phpcs
/app $ exit
```

The tools Docker image bakes the WordPress test suite in at build time, so local
integration runs are fast; only the database is prepared at run time.

---

## 5. Coding standards & static analysis

- **PHPCS** (`phpcs.xml.dist`) uses `WPTechnix-PSR4` + `WPTechnix-Strict`: WordPress
  core style with PSR-4 file names, short array syntax (`[]`), and modern-PHP quality
  sniffs. Text domain is `wp-plugin-boilerplate`. Run: `./scripts/dev phpcs` (auto-fix
  with `./scripts/dev phpcbf`).
- **PHPStan** (`phpstan.neon.dist`) runs at level 8, `phpVersion` 8.0, with WordPress
  and WooCommerce stubs. Run: `./scripts/dev phpstan`.
- **PHP target is 8.0** and is declared consistently in the plugin header,
  `plugin/composer.json`, `phpcs.xml.dist` (`testVersion 8.0-`) and `phpstan.neon.dist`
  (`phpVersion 80000`). Keep all four in step if you change it. The committed
  `composer.lock` must also stay installable on the lowest supported PHP (the unit
  matrix installs it on 8.0). The `config.platform.php` setting (`"8.0"`) in the
  root `composer.json` makes Composer enforce this automatically.
- **EditorConfig** is enforced in CI only (there is no local checker). `*.php` uses
  tabs (`indent_size = 4` display width, `max_line_length = 120`); the shell scripts
  use spaces (4-space for `dev`/`scope`/`setup`/`lib/runtime`, 2-space for
  `install-wp-tests`). CI runs the checker with `-disable-max-line-length` so line
  length stays owned by PHPCS, which understands PHP context.

> Typed properties are documented **without** a `@var` tag (the native type is
> self-documenting) via two `severity 0` overrides in `phpcs.xml.dist`. Left alone the
> base standard deadlocks on typed properties: WordPress-Docs demands a `@var`, Slevomat
> forbids one that merely repeats the type. See the comment there before touching it.

---

## 6. Testing

Two suites with separate bootstraps and configs. See
[tests index](tests/INDEX.md) for the quick reference and
[testing docs](docs/testing.md) for the full detail:

- **Unit** (`tests/Unit/`, `phpunit-unit.xml.dist`) — extend
  `Yoast\WPTestUtils\BrainMonkey\TestCase`. No WordPress is loaded; mock it with Brain
  Monkey. Fast, run anywhere: `./scripts/dev test:unit`.
- **Integration** (`tests/Integration/`, `phpunit-integration.xml.dist`) — extend
  `Yoast\WPTestUtils\WPIntegration\TestCase`. Runs against real WordPress + MySQL:
  `./scripts/dev test:integration` (it starts the database and prepares the suite for
  you).

Both suites load the plugin from `plugin/`: the integration bootstrap `require`s the
main plugin file on `muplugins_loaded`, and the root autoloader maps
`WPPluginBoilerplate\ => plugin/src/` for both suites.

**PHPUnit is pinned to `^9.6` and must not be bumped.** `yoast/wp-test-utils` 1.x
depends on `phpunit-polyfills` 1.x, which caps PHPUnit at 9.6. Bumping PHPUnit breaks
the integration suite.

---

## 7. Dependency management & scoping

- **Runtime** dependencies of the plugin go in **`plugin/composer.json`**. When you add
  one, pin `config.platform.php` there to the plugin's PHP floor (`8.0`) so the release
  build (which runs on a newer PHP) can't resolve a dependency version that needs a PHP
  newer than the plugin claims to support.
- **Development** dependencies (test/lint/analysis tooling) go in the **root**
  `composer.json`.
- The plugin **always** loads its code from `plugin/vendor-prefixed/`, which
  `scripts/scope` builds both for a release and for local development, so what runs
  locally matches what ships. Third-party dependencies there are **prefixed**
  (namespaced under `WPPluginBoilerplate_Deps`), so two plugins bundling the same
  library at different versions cannot clash.
- Use `./scripts/dev plugin <command>` to run Composer against `plugin/composer.json`.
  It automatically triggers `scripts/scope` after `require`, `remove`, `install` and
  `update`, so `plugin/vendor-prefixed/` stays in sync without a manual step.
- With **no runtime dependencies** (the default), scoping is a clean no-op: Composer's
  own autoloader is moved into `plugin/vendor-prefixed/` unchanged, with nothing to prefix.
- The main plugin file loads, in order,
  `plugin/vendor-prefixed/scoper-autoload.php` → `plugin/vendor-prefixed/autoload.php`.
  If neither exists (a plain development checkout that has not run `scripts/scope`), it
  still boots, because the root Composer autoloader has already registered the plugin's
  classes; the guard is `class_exists( Plugin::class )`, not "does a file exist".

Full mechanism: [architecture docs](docs/architecture.md).

---

## 8. Release process

Releases are driven by pushing a Git tag; see
[workflows docs](docs/workflows.md) for the full pipeline.

1. Bump the version in **two** places in the main plugin file: the `Version:` header
   and the `*_VERSION` constant, and commit.
2. Tag and push:
   ```
   git tag v1.2.3
   git push origin v1.2.3
   ```
   A tag with a hyphen (e.g. `v1.2.3-beta.1`) is published as a **pre-release**. For
   pre-releases, only the tag suffix differs: the plugin file stays at the stable
   version (`1.2.3`).
3. `release.yml` verifies the tag's base version (pre-release suffix stripped) matches
   both version locations (it fails the build on mismatch), generates the translation
   template into `plugin/languages/` with `wptechnix/wp-pot-generator`, scopes the
   dependencies, builds `<slug>-<version>.zip` (Composer manifests stripped), and
   publishes a GitHub release with the zip attached.

---

## 9. CI workflows

- **`lint.yml`** — PHPCS (annotated inline via `cs2pr`), PHPStan (GitHub error format)
  and editorconfig-checker on every PR and push to `main`.
- **`test.yml`** — unit tests across PHP 8.0–8.4; integration tests across a PHP ×
  WordPress matrix (latest, a previous release, and nightly-allowed-to-fail) with a
  MySQL service. Add or remove WordPress versions in the matrix `include:` list.
- **`release.yml`** — tag-triggered build and publish (§8).
- **`commitlint.yml`** — Conventional Commits validation.

---

## 10. VCS discipline (rules for every contributor and agent)

- **Never commit to `main` directly.** Create a feature branch and open a Pull
  Request. Branch names: `feat/…`, `fix/…`, `chore/…`, `docs/…`.
- **Conventional Commits** are required (enforced by commitlint and the pre-commit
  Husky hook). Example: `feat(admin): add settings page`.
- **Keep history clean.** Prefer small, focused commits; rebase to tidy up before
  merging rather than piling on "fix" commits. Don't merge `main` into your branch
  repeatedly. Rebase instead.
- **CI must be green before merge.** Lint and tests are the gate; the Husky hooks
  (`pre-commit` runs phpcbf, `pre-push` runs phpcs + phpstan) are a local convenience,
  not the gate.
- **Never commit generated artifacts:** `vendor/`, `plugin/vendor/`,
  `plugin/vendor-prefixed/`, `plugin/composer.lock`, `node_modules/`, build zips or
  caches are all gitignored; keep it that way.
- **Match the surrounding style.** Tabs in `*.php`, short arrays, WordPress naming.
  Run `./scripts/dev phpcbf` before committing.
- **Keep the four PHP-version declarations in sync** (§5) and **do not bump PHPUnit
  past 9.6** (§6) if you touch dependencies.
- When you change behaviour, add or update a test in the matching suite.

---

## 11. Further reading

- [Scripts index](scripts/INDEX.md) — the development CLI and every script.
- [Tests index](tests/INDEX.md) — the test suites at a glance.
- [Architecture docs](docs/architecture.md) — structure, autoloading, and the
  dependency-scoping model in depth.
- [Testing docs](docs/testing.md) — how the unit and integration suites are wired.
- [Workflows docs](docs/workflows.md) — CI and the release pipeline in depth.
