# Plugin Tests: what is covered

This file lists what the **Plugin Tests** GitHub Actions workflow checks, and what isn't covered yet.
Update it in the same commit when you add or change a test.

## How to run

GitHub > Actions > **Plugin Tests (SDM)** > Run workflow. Choose the branch and tick the test groups to run (all are ticked by default).
The workflow file is [.github/workflows/plugin-tests.yml](../.github/workflows/plugin-tests.yml).
Run it before a release and after bigger changes.

A separate, light workflow, [.github/workflows/php-compatibility-test.yml](../.github/workflows/php-compatibility-test.yml), runs on every push and pull request to master
(it activates the plugin and runs the shortcodes). Keep it fast; full tests go in the Plugin Tests workflow.

| Group | What it is | PHP versions | Files |
|---|---|---|---|
| Lint | PHP syntax check of the plugin and the test files | 7.4, 8.5 | (in the workflow) |
| PHPUnit | Tests with the WordPress test library and a real database | 7.4, 8.4 | [phpunit/](phpunit/) |

Which group a new test belongs in:
- Plugin functions and classes that need WordPress and the database: **PHPUnit**.
- The code runs from an incoming request (the download request `/?sdm_process_download=1&download_id=N`) and ends with `exit` or `wp_die()`:
  **Endpoint tests** (planned, see "Not covered yet").

PHPUnit converts PHP notices and warnings into test failures, so a notice in the plugin code fails the test that runs it.
The test files must stay PHP 7.4-compatible (the oldest PHP version in the workflow).

---

## PHPUnit ([phpunit/tests/](phpunit/tests/))

**Plugin smoke test** (test-plugin-smoke.php)
- The plugin loads (main classes and the version constant; `WP_SDM_LOG_FILE` is still defined for backward compatibility).
- Activation creates the `sdm_downloads` log table with all its columns, and saves the DB version and the activation time.
- Activating again (as a plugin update can) keeps the activation time.
- The `sdm_downloads` post type and the `sdm_categories` and `sdm_tags` taxonomies are registered.
- All the shortcodes are registered, and each one runs without attributes without a PHP error, warning or notice.
- `[sdm_download]` without an ID shows an error; with an ID it shows the download button with the download URL and the default or custom button text.
- `[sdm_download_link]` returns the download URL.
- The content filters are registered (from the old tests); the title filter leaves other titles unchanged.
- The single download page shows the download button, or the "disabled" message when the page is disabled.
- The download description is formatted (`wpautop`).

---

## Known issues (tests that report without failing)

A test for a known or suspected bug runs, but doesn't fail the workflow, so the output shows on every run whether the problem still happens:
the test calls `markTestIncomplete()` when the bug happens (shown as incomplete, with the reason); any other wrong result fails.
The commit that fixes an issue removes the `markTestIncomplete()` part, so its test becomes a normal test.

Not tested yet:
- `[sdm_download_categories]` declares the function `custom_taxonomy_walker()` inside the shortcode handler, so a second
  `[sdm_download_categories]` on the same page (or a second call in the same request) is a PHP fatal error ("Cannot redeclare").
  It is left out of the smoke test's shortcode list until it is fixed (a fatal error would stop the whole test run).

Fixed while adding the tests:
- `[sdm_download]` without attributes read `$atts['id']` from the attributes WordPress passes: an empty array (WordPress 6.5+, a PHP warning)
  or an empty string (WordPress 6.0 to 6.4, a PHP fatal error on PHP 8).

---

## Not covered yet (ideas for new tests)

Phase B: PHPUnit tests for the recent security fixes
- The file path resolver (`get_uploaded_file_path_from_url()`): path traversal, the base URL check (http and https), the always-blocked extensions.
- The debug log: the `logs/` folder, the per-site file name prefix, `.htaccess` and `index.html`, removal of the old log file.
- The export logs AJAX handler: the permission check, the sort column check, no database error in the response.
- The download item editor doesn't save a file URL with `../`.
- The shortcodes don't show private, draft or unpublished items (4.1.3 and 4.1.4 fixes).

Phase C: Endpoint tests (a real WordPress site started with `php -S`, requests sent over HTTP)
- The download request: redirect and PHP dispatch, the protected folder, password-protected items, the logged-in-only option,
  the download log entry, and a path traversal URL that must not return a file from outside `wp-content`.

Phase D: other features
- The logs admin page (sorting, search, delete), the CSV export, the statistics, the other shortcodes, "delete all plugin data".

Later
- The add-ons, tested with the core plugin installed.
