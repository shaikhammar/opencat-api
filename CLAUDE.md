# OpenCAT API — Project Notes

## Tool paths (Laravel Herd)

**PHP 8.4:** `C:/Users/shaik/.config/herd/bin/php84/php.exe`
**Composer:** `C:/Users/shaik/.config/herd/bin/composer.bat`

Run artisan: `"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan <command>`
Run tests: `"C:/Users/shaik/.config/herd/bin/php84/php.exe" artisan test --compact`

## Framework packages

opencat/* packages are resolved via Composer path repositories pointing to `../opencat-framework/packages/*`.
Do not `composer require opencat/*` — they are already wired in `composer.json`.

## Skills

- `opencat-versioning-check` — MUST activate before every `git push` or `gh pr create` in this repo. Verifies all `opencat/*` dependencies use `^0.1` (not `*`), and that a breaking change bumps all packages together.
