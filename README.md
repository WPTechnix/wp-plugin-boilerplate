# WP Plugin Boilerplate

**WP Plugin Boilerplate** is a WordPress plugin that ships with a batteries-included
development setup: a Dockerised dev environment, coding standards, static analysis, unit
and integration tests, dependency scoping, and CI/release automation, all wired up and
ready to use, so development is productive from the first commit.

## Requirements

- **Docker** (Desktop or Engine, with the Compose v2 plugin).

That's it: PHP, Composer, MySQL and WP-CLI all run in containers. No host PHP needed.

## Quick start

```sh
# 1. Make it yours: interactive wizard sets name, slug, namespace, text domain, ports.
./scripts/dev setup

# 2. Install dependencies
./scripts/dev composer install

# 3. Start the stack
./scripts/dev up

# 4. Run checks and tests
./scripts/dev phpcs
./scripts/dev phpstan
./scripts/dev test    # creates the test database automatically
```

For the full command reference and details about every script (environment, scoping,
test setup, Docker config), see the [scripts index](scripts/INDEX.md).

## Documentation

- [Scripts index](scripts/INDEX.md) — the development CLI and scripts folder.
- [Tests index](tests/INDEX.md) — the test suites at a glance.
- [Contributor and agent guide](AGENTS.md) — full contributor & agent guide (structure, commands, rules).
- [Architecture docs](docs/architecture.md) — structure, autoloading, scoping.
- [Testing docs](docs/testing.md) — the test suites in depth.
- [Workflows docs](docs/workflows.md) — CI and the release pipeline.

## Releasing

Bump the `Version:` header and the `*_VERSION` constant in the main plugin file, then:

```sh
git tag v1.2.3
git push origin v1.2.3
```

The release workflow verifies the version, scopes dependencies, generates the `.pot`
file, and publishes a GitHub release with the plugin zip. Hyphenated tags
(`v1.2.3-beta.1`) publish as pre-releases.

## License

GPL-2.0-or-later.
