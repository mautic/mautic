# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Background
The project is built using PHP and follows the Symfony framework conventions. It is important to adhere to the coding standards and practices established in the original Mautic project to ensure consistency and maintainability.
We have two git branches:
1. `staging`: This project is based on Mautic 4.x and is a fork of the original Mautic project. It is designed to be a self-hosted marketing automation platform that allows users to manage their marketing campaigns, leads, and customer interactions.
2. `7.x`: This project is fork of Mautic 7.x which is based on Symfony 6.x framewok 

## Build & Test Commands
- Full test suite: `ddev composer test`
- Single test: `ddev composer test -- path/to/TestFile.php`
- Single test method: `ddev composer test -- --filter testMethodName path/to/TestFile.php`
- PHP Static Analysis: `ddev composer phpstan`
- Code Style Check: `ddev composer cs`
- Fix Code Style: `ddev composer fixcs`

## Code Style Guidelines
- Follow Symfony code style (`@Symfony` ruleset)
- Use PSR-4 autoloading standards
- Use short array syntax `[]` instead of `array()`
- Align operators (=> and =)
- Order imports alphabetically
- Class naming: `Mautic\[Bundle]\[Type]\[Name]`
- Bundle structure follows Symfony conventions
- Use modern PHP features:
    - Use constructor property promotion for clean class definitions
    - Use typed properties with appropriate type hints (e.g., `private string $name`)
    - Use return type declarations for all methods (e.g., `public function getName(): string`)
    - Use nullable types with ? prefix (e.g., `?string` instead of `string|null`)
    - Use readonly properties when applicable for immutability
- Error handling: Use try/catch blocks with specific exception handling
- Documentation: Use DocBlocks for methods and classes with up-to-date parameter and return types
- Tests: Organized in `Tests/Unit` and `Tests/Functional` directories
- Branch naming: `epic-[feature]` pattern is used
- Labels and messages would be added in `.ini` files under `Translations` directory of respective bundles
- Split the lines which are longer than 120 characters
- Make sure all the code passes phpstan and phpcs checks
- Always add an empty new line at the end of the file
- Whenever a JS file is modified, make sure to run `ddev php bin/console mautic:assets:generate` to generate the new JS files

## Whitespace Guidelines
- CRITICAL: Do not add any spaces or tabs on empty lines; empty lines MUST be completely blank without any whitespace characters
- Do not leave trailing whitespace at the end of any line
- Use consistent indentation (prefer spaces over tabs)
- Always check all edits for unintended whitespace before submitting changes


See full contributing guidelines at https://contribute.mautic.org/contributing-to-mautic/developer
