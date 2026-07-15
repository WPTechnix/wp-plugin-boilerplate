# tests/

Unit and integration test suites for the plugin. See the
[scripts index](../scripts/INDEX.md) for the commands to run them.

```
tests/
├── bootstrap/
│   ├── unit.php           # Brain Monkey bootstrap (no WordPress)
│   └── integration.php    # WordPress test-suite bootstrap (real DB)
├── Unit/                  # Unit tests  (WPPluginBoilerplate\Tests\Unit\)
└── Integration/           # Integration tests (…\Tests\Integration\)
```

## Unit suite

- **Config:** `phpunit-unit.xml.dist` (repo root)
- **Test cases extend:** `Yoast\WPTestUtils\BrainMonkey\TestCase`
- **No WordPress loaded** — mock WordPress functions with Brain Monkey.
- Fast and run anywhere: `./scripts/dev test:unit`

Write unit tests for logic that can be isolated from WordPress. Mock the seams.

## Integration suite

- **Config:** `phpunit-integration.xml.dist` (repo root)
- **Test cases extend:** `Yoast\WPTestUtils\WPIntegration\TestCase`
- Runs against **real** WordPress + MySQL.
- Backed by the [`scripts/install-wp-tests` script](../scripts/INDEX.md#install-wp-tests): run
  `./scripts/dev test:setup` to create the test database, then
  `./scripts/dev test:integration` to execute the suite.

Write integration tests for behaviour that depends on WordPress hooks, options, or the
database.

## PHPUnit ceiling

Keep `phpunit/phpunit` at `^9.6`. `yoast/wp-test-utils` 1.x depends on
`phpunit-polyfills` 1.x, capping PHPUnit at 9.6. Bumping it breaks the integration
suite.

---

See the [testing docs](../docs/testing.md) for the full deep-dive.
