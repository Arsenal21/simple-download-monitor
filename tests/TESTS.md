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

**File path resolver** (test-file-path-resolver.php, `SDM_Utils_File_System_Related`). The path traversal report of November 2026 (CVE-2026-135625, fixed in 4.1.6).
- A file in the protected folder (`uploads/sdm-uploads`, also in a subfolder) and a file in the uploads folder resolve to their path; http and https URLs both work.
- Rejected (`false`): path traversal to the WordPress root or out of the protected folder (`../`, URL-encoded and double URL-encoded dots,
  URL-encoded slashes, backslashes, a `./` segment), a URL that doesn't start with the folder's URL (another site, another folder ending with
  `uploads/sdm-uploads`), an external URL, a missing file, the folder itself, an empty or invalid value.
- `path_has_traversal()`: `.` and `..` segments (also encoded, double encoded, with backslashes) are found; dots inside file names,
  hidden files and `../` in the query string are not.
- PHP-type extensions (`php`, `php3`–`php8`, `phps`, `phtml`, `phar`, `pht`, in any case) are always blocked from PHP dispatch, even with a saved list
  of blocked extensions; the default list (`php, config, bak`) is used when none is saved; the saved list is used otherwise.
- Hidden files and files without an extension are detected.

**Debug log** (test-debug-log.php, `SDM_Debug`). The debug log report of November 2026 (fixed in 4.1.5).
- The log file is in the plugin's `logs/` folder and its name has a random prefix; the prefix is saved and stays the same; `WP_SDM_LOG_FILE` points to it.
- A new prefix is made when none is saved, or when the saved one isn't valid; two sites get different prefixes.
- Nothing is written when debug is off; messages and arrays are written when it is on.
- Writing the log creates `.htaccess` (Apache 2.2 and 2.4 rules) and an empty `index.html` when they are missing; the plugin ships them too.
- Reset replaces the content; the log file can be deleted.
- The old `sdm-debug-log.txt` in the plugin's main folder is deleted (also on `admin_init`).

**Export logs** (test-export-logs.php, `sdm_export_logs_handler()`). GitHub issue #150 (fixed in 4.1.6).
The WordPress test library skips the `ajax` test group, so the handler is called directly (with `wp_doing_ajax()` on and `wp_die()` throwing).
- Rejected: a wrong nonce, and a user without the plugin's admin permission (even with a valid nonce).
- The logs are exported with all the columns, newest first by default; more than 100 entries (the export reads 100 at a time).
- Sorting by every sortable column of the Logs table, both directions.
- An unknown column (the report), SQL in the column or the direction, and empty values don't cause an error: an invalid column
  falls back to `id`, and an invalid direction to `DESC` (a valid direction is kept, so an unknown column with `ASC` is sorted by `id ASC`).
- A database error isn't sent to the browser (a generic message is); it is written to the debug log.
- The search finds by name and IP address; `%` and `_` are searched as characters; quotes work.

**Download item editor** (test-edit-download-save.php, `SDM_Admin_Edit_Download::save_post_handler()`). CVE-2026-135625 (fixed in 4.1.6).
- A normal file URL is saved.
- A file URL with `../` (the report), URL-encoded dots or a `./` segment isn't saved: the previous URL stays and the error notice is set.
- `../` in the query string is allowed.
- The error notice is shown once.
- A save without the nonce is rejected.

**Shortcode availability** (test-shortcode-availability.php). Fixed in 4.1.3 and 4.1.4.
- `[sdm_download]` shows a published item, and hides a private, draft, pending or scheduled one (unless the user can edit it,
  or "Allow downloads of unpublished items" is on); an ID that isn't a download item shows an error.
- `[sdm_show_download_info]` shows a published item's info, and hides the title, description and download URL of an unavailable item;
  `download_url` is the standard download URL for a password-protected item, and the file URL otherwise.
- `[sdm_popular_downloads]` and `[sdm_latest_downloads]` list only published items; with the option on, the popular downloads
  also list unpublished items, but not trashed ones.
- `[sdm_download_categories]` can be used twice on a page, shows nested categories, and shows an empty tree without categories.

---

## Known issues (tests that report without failing)

A test for a known or suspected bug runs, but doesn't fail the workflow, so the output shows on every run whether the problem still happens:
the test calls `markTestIncomplete()` when the bug happens (shown as incomplete, with the reason); any other wrong result fails.
The commit that fixes an issue removes the `markTestIncomplete()` part, so its test becomes a normal test.

There are none at the moment.

Fixed while adding the tests:
- `[sdm_download]` without attributes read `$atts['id']` from the attributes WordPress passes: an empty array (WordPress 6.5+, a PHP warning)
  or an empty string (WordPress 6.0 to 6.4, a PHP fatal error on PHP 8).
- `[sdm_download_categories]` declared the function `custom_taxonomy_walker()` inside the shortcode handler, so a second
  `[sdm_download_categories]` on the same page was a PHP fatal error ("Cannot redeclare"). It is the top-level function
  `sdm_download_categories_tree_walker()` now (it also escapes the category name and slug).

---

## Not covered yet (ideas for new tests)

Phase C: Endpoint tests (a real WordPress site started with `php -S`, requests sent over HTTP)
- The download request: redirect and PHP dispatch, the protected folder, password-protected items, the logged-in-only option,
  the download log entry, and a path traversal URL that must not return a file from outside `wp-content`.

Phase D: other features
- The logs admin page (sorting, search, delete), the CSV export, the statistics, the other shortcodes, "delete all plugin data".

Later
- The add-ons, tested with the core plugin installed.
