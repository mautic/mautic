# Repository Guidelines

## Project Structure & Module Organization
- Core library: `app/` (Symfony-based Mautic core).
- Plugins: `plugins/` (autoloaded under `MauticPlugin\\`), themes in `themes/`.
- Config and runtime: `config/`, environment files `.env*`, cache/logs in `var/`.
- Front controller and assets: `index.php`, static/media in `media/`, Twig templates in `templates/`.
- Tooling: `bin/` (CLI tools), `vendor/` (Composer), `node_modules/` (frontend).
- Tests: `tests/` (PHPUnit, Codeception acceptance).

## Build, Test, and Development Commands
- Install deps: `composer install && npm ci` (Composer + Node).
- Build assets: `npm run build` (webpack) or `composer generate-assets` (`bin/console mautic:assets:generate`).
- Unit tests: `composer test` (PHPUnit; config at `app/phpunit.xml.dist`).
- E2E tests: `composer e2e-test` (Codeception acceptance).
- Static analysis: `composer phpstan`.
- Lint/format: `composer cs` (dry-run) and `composer fixcs`.
- Refactors: `composer rector`.

## Coding Style & Naming Conventions
- PHP 8.2, PSR-12, 4-space indentation.
- Classes `StudlyCaps`, methods/props `camelCase`, constants `UPPER_SNAKE`.
- Namespaces: plugins under `MauticPlugin\\VendorBundle` (autoloaded via `plugins/{$name}`).
- Keep controllers/services thin; prefer DI, no facades. Run `composer cs` before commits.

## Testing Guidelines
- Frameworks: PHPUnit 10 (`tests/`), Codeception acceptance (`codeception.yml`).
- Add/adjust tests with every fix/feature; prefer small, focused unit tests.
- Naming: `*Test.php` mirroring source (e.g., `tests/Unit/Email/EmailHelperTest.php`).
- Run `composer test` locally; for UI flows add acceptance tests where relevant.

## Commit & Pull Request Guidelines
- Commit style: short imperative summary, context in body, reference issues (e.g., `MTC-1234`, `#15305`).
- Scope prefixes optional (e.g., `plugins:`, `core:`) when helpful.
- PRs must include: purpose/changes, testing steps, linked issue, screenshots for UI, and notes on migrations/config if any.
- Ensure CI passes: tests, CS, PHPStan, and assets build.

## Security & Configuration Tips
- Never commit secrets; use `.env.local` and environment variables.
- Use `APP_ENV=test` for local analysis (`composer phpstan`).
- Report vulnerabilities per `SECURITY.md`.
