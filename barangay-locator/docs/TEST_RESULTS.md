# Test Results

**Honest status: nothing has been executed.** No PHP interpreter, MySQL server or browser was available during the build, so no test below has been run. "Not executed" is the only valid status until someone runs it and records the output here.

| ID | Test | Status | Evidence |
|---|---|---|---|
| U-01 | `php tests/run_tests.php` (validators, SQL splitter, i18n key parity) | **Not executed** | — |
| D-01 | `php tests/db_tests.php …` (public visibility rules on sample data) | **Not executed** | — |
| L-01 | `php -l` on every `.php` file | **Not executed** | — |
| I-01 | Installer on a clean cPanel DB | **Not executed** | — |
| I-02 | Installer refuses to run twice (lock file) | **Not executed** | — |
| F-01…F-12 | Manual walkthrough in TEST_PLAN.md | **Not executed** | — |
| S-01…S-10 | Security checks in TEST_PLAN.md | **Not executed** | — |

## How to fill this in
For each run record: date, who ran it, environment (PHP/MySQL versions, cPanel or XAMPP), result (Pass/Fail), and paste failure output. Change a row to **Pass** only with evidence.

## Static review performed
Code was re-read by its author for logic and security issues while writing (e.g. fixed: PHP quoting bug in the installer SQL, invalid `<tr><form>` markup, timing difference for unknown users at login). This is *not* a substitute for running the tests.
