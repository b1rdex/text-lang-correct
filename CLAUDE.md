# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install --no-interaction --no-progress

# Run tests
vendor/bin/phpunit

# Run a single test method
vendor/bin/phpunit --filter testMethodName

# Static analysis (level 5)
vendor/bin/phpstan analyse
```

## Architecture

This is a PHP library (`B1rdex\Text` namespace) for correcting keyboard layout mistakes in text (e.g., Russian typed with English keys or vice versa).

**Core class:** `src/LangCorrect.php`
- Main entry point: `parse(string $s, int $mode, array &$words): string|bool`
- Two correction modes via constants:
  - `SIMILAR_CHARS` – fast, replaces visually similar characters across layouts
  - `KEYBOARD_LAYOUT` – slower, uses N-gram language analysis to correct entire words typed in the wrong layout
- Both modes can be combined with bitwise OR; `ADD_FIX` flag appends corrections to `$words` without replacing in the original string
- The `$words` parameter is passed by reference and returns a map of `original => corrected` words

**Utilities:**
- `src/Util/UTF8.php` – UTF-8 string handling without requiring mbstring/iconv; key methods are `diactrical_remove()` and `diactrical_restore()` used to strip/restore combining diacritical marks during processing
- `src/Util/ReflectionTypeHint.php` – runtime parameter type validation via PHP Reflection; can be disabled in production via `assert` settings

**Data flow:** Input UTF-8 text → strip diacriticals → extract words (≥3 chars via regex) → apply correction mode(s) → restore diacriticals → return corrected text + word map.

**Tests:** Single file `tests/LangCorrectTest.php` with 4 test methods covering KEYBOARD_LAYOUT, SIMILAR_CHARS, skip-known-words behavior, and stress scenarios with complex Russian-English phrases.
