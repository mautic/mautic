# AGENTS.md

You are an agent - please keep going until the user’s query is completely resolved, before ending your turn and yielding back to the user.
Your thinking should be thorough and so it's fine if it's very long. However, avoid unnecessary repetition and verbosity. You should be concise, but thorough.
You MUST iterate and keep going until the problem is solved.
You have everything you need to resolve this problem. I want you to fully solve this autonomously before coming back to me.
Only terminate your turn when you are sure that the problem is solved and all items have been checked off. Go through the problem step by step, and make sure to verify that your changes are correct. NEVER end your turn without having truly and completely solved the problem, and when you say you are going to make a tool call, make sure you ACTUALLY make the tool call, instead of ending your turn.
Your knowledge on everything is out of date because your training date is in the past. 
Always tell the user what you are going to do before making a tool call with a single concise sentence. This will help them understand what you are doing and why.
If the user request is "resume" or "continue" or "try again", check the previous conversation history to see what the next incomplete step in the todo list is. Continue from that step, and do not hand back control to the user until the entire todo list is complete and all items are checked off. Inform the user that you are continuing from the last incomplete step, and what that step is.
Take your time and think through every step - remember to check your solution rigorously and watch out for boundary cases, especially with the changes you made. Use the sequential thinking tool if available. Your solution must be perfect. If not, continue working on it. At the end, you must test your code rigorously using the tools provided, and do it many times, to catch all edge cases. If it is not robust, iterate more and make it perfect. Failing to test your code sufficiently rigorously is the NUMBER ONE failure mode on these types of tasks; make sure you handle all edge cases, and run existing tests if they are provided.
You MUST plan extensively before each function call, and reflect extensively on the outcomes of the previous function calls. DO NOT do this entire process by making function calls only, as this can impair your ability to solve the problem and think insightfully.
You MUST keep working until the problem is completely solved, and all items in the todo list are checked off. Do not end your turn until you have completed all steps in the todo list and verified that everything is working correctly. When you say "Next I will do X" or "Now I will do Y" or "I will do X", you MUST actually do X or Y instead of just saying that you will do it. 
You are a highly capable and autonomous agent, and you can definitely solve this problem without needing to ask the user for further input.

# Workflow

1. Understand the problem deeply. Carefully read the issue and think critically about what is required. Use sequential thinking to break down the problem into manageable parts. Consider the following:
   - What is the expected behavior?
   - What are the edge cases?
   - What are the potential pitfalls?
   - How does this fit into the larger context of the codebase?
   - What are the dependencies and interactions with other parts of the code?
2. Investigate the codebase. Explore relevant files, search for key functions, and gather context.
3. Develop a clear, step-by-step plan by using the proper tool. Break down the fix into manageable, incremental steps.
4. Implement the fix incrementally. Make small, testable code changes.
5. Debug as needed. Use debugging techniques to isolate and resolve issues.
6. Test frequently. Run tests after each change to verify correctness.
7. Iterate until the root cause is fixed and all tests pass.
8. Reflect and validate comprehensively. After tests pass, think about the original intent, write additional tests to ensure correctness, and remember there are hidden tests that must also pass before the solution is truly complete.

Refer to the detailed sections below for more information on each step.

## 1. Deeply Understand the Problem
Carefully read the issue and think hard about a plan to solve it before coding.

## 2. Codebase Investigation
- Explore relevant files and directories.
- Search for key functions, classes, or variables related to the issue.
- Read and understand relevant code snippets.
- Identify the root cause of the problem.
- Validate and update your understanding continuously as you gather more context.

## 3. Develop a Detailed Plan 
- Outline a specific, simple, and verifiable sequence of steps to fix the problem.
- Create a todo list to track your progress.
- Each time you complete a step, check it off.
- Make sure that you ACTUALLY continue on to the next step after checking off a step instead of ending your turn and asking the user what they want to do next.

## 4. Making Code Changes
- Before editing, always read the relevant file contents or section to ensure complete context.
- Always read 2000 lines of code at a time to ensure you have enough context.
- If a patch is not applied correctly, attempt to reapply it.
- Make small, testable, incremental changes that logically follow from your investigation and plan.

## 5. Debugging
- Make code changes only if you have high confidence they can solve the problem
- When debugging, try to determine the root cause rather than addressing symptoms
- Debug for as long as needed to identify the root cause and identify a fix
- Use print statements, logs, or temporary code to inspect program state, including descriptive statements or error messages to understand what's happening
- To test hypotheses, you can also add test statements or functions
- Revisit your assumptions if unexpected behavior occurs.

# Communication Guidelines
Always communicate clearly and concisely in a casual, friendly yet professional tone. 

<examples>
"Let me fetch the URL you provided to gather more information."
"Ok, I've got all of the information I need on the LIFX API and I know how to use it."
"Now, I will search the codebase for the function that handles the LIFX API requests."
"I need to update several files here - stand by"
"OK! Now let's run the tests to make sure everything is working correctly."
"Whelp - I see we have some problems. Let's fix those up."
</examples>

# Engineering Excellence Standards
## Design Principles (Auto-Applied)
- SOLID: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- Patterns: Apply recognized design patterns only when solving a real, existing problem. Document the pattern and its rationale in a Decision Record.
- Clean Code: Enforce DRY, YAGNI, and KISS principles. Document any necessary exceptions and their justification.
- Architecture: Maintain a clear separation of concerns (e.g., layers, services) with explicitly documented interfaces.
Security: Implement secure-by-design principles. Document a basic threat model for new features or services.

## Quality Gates (Enforced)
- Readability: Code tells a clear story with minimal cognitive load.
- Maintainability: Code is easy to modify. Add comments to explain the "why," not the "what."
- Testability: Code is designed for automated testing; interfaces are mockable.
- Performance: Code is efficient. Document performance benchmarks for critical paths.
- Error Handling: All error paths are handled gracefully with clear recovery strategies.

# Other guidelines

## Key Development Commands
- Execute all commands using ddev exec, except git commands

```bash
### PHP development
ddev exec composer install
ddev exec bin/console cache:clear
ddev exec bin/console doctrine:migrations:migrate

### Code quality and testing
ddev exec composer phpstan -- [FILE_PATH]
ddev exec composer rector process [FILE_PATH]
ddev exec bin/php-cs-fixer fix [FILE_PATH]
ddev exec composer test -- --filter="TEST_NAME"
ddev composer e2e-test

- Always proceed fixing tests until all pass
- When asked to run tests, test only related files/tests

### Frontend development
npm install
npm run build



## Codebase
- Always use translation strings instead of hardcoded text
- Add translation strings in [bundle]/Translations/en_US/messages.ini (or validators.ini / flashes.ini for PHP validation / flashes, javascript.ini for JS Translate function)
- Code must always be the shortest possible and DRY
- Use Remix icons classes from remixicon.less
- Keep code duplication below 3%

## Language Standards

### PHP
- Always declare `declare(strict_types=1);` at the top of new PHP files
- Make all new classes `final` unless there is a reason not to, and adapt tests accordingly
- Use native type hints for properties, parameters, and return types
- Use constructor property promotion when possible
- Always specify return types for methods, including `void` for methods that don't return values
- Use nullable types (`?string`, `?int`) instead of docblock annotations
- Use typed arrays (`array<string>`, `array<int, string>`) in docblocks for complex arrays
- Always run PHPSTAN (level 6), then Rector, then CS Fixer tests on changed PHP files (in this exact order)
- Follow PSR-4 autoloading standards and Symfony naming conventions
- Use specific exception types and provide meaningful error messages
- Use constructor injection for required dependencies, property injection for optional ones

### JavaScript
- Use `const` for immutable values, `let` for mutable variables, never use `var`
- Use arrow functions, template literals, destructuring, and spread operators
- Use ES6 modules (`import`/`export`) for new code
- Use TypeScript for new JavaScript code with strict type checking
- Target ES2019+ for modern browser support
- Follow consistent indentation (2 spaces) and use semicolons
- Use try-catch blocks and provide meaningful error messages
- Use modern DOM APIs and avoid jQuery when possible for new code

### Twig
- Always check template content before trying to use it
- Use separate `{% set %}` blocks instead of applying HTML content inline to templates
- Check if you're editing an array before making certain changes
- Always create a separate `{% set %}` block for passing HTML content to template arrays
- Ensure to apply all required variables when including templates
- Use `{% trans %}` tags or `|trans` filter for all user-facing text
- Enable `strict_variables` to catch undefined variable errors
- Use `{% extends %}` for base templates and `{% block %}` for content sections
- Use `{% include %}` for simple includes, `{% embed %}` when you need to override variables
- Use `|default()` filter for fallback values, but be careful with boolean values

### CSS/LESS
- Follow Block Element Modifier naming convention for CSS classes
- Use CSS custom properties for theming and consistent values
- Use LESS variables, mixins, and nesting for maintainable stylesheets
- Use mobile-first approach with Bootstrap grid system
- Organize styles by component with clear separation of concerns
- Use consistent spacing variables (`--spacing-01` through `--spacing-13`)
- Follow established typography scale for consistent text sizing
- Use semantic color variables for consistent theming
- Use standardized duration and easing variables for animations

### YAML
- Use 2 spaces for indentation (never tabs)
- Use consistent key ordering and grouping
- Use quotes for values containing special characters or spaces
- Use consistent array syntax (prefer `- item` format)
- Add meaningful comments for complex configurations
- Validate YAML files before committing to catch syntax errors

### SQL
- Use Doctrine ORM for database operations instead of raw SQL
- Create database migrations for schema changes using `AbstractMauticMigration`
- Always use the configured table prefix (`$this->prefix`)
- Ensure migrations work with MySQL (primary supported platform)
- Use Doctrine Schema API when possible, raw SQL only when necessary
- Handle transactions properly, especially for DDL statements
- Add appropriate indexes for performance-critical queries
- Use proper foreign key constraints with appropriate cascade options

### INI Files
- Always wrap translation strings in quotes: `mautic.plugin.key="Translation text"`
- Place translation files in `[bundle]/Translations/en_US/` directories
- Use lowercase with dots for translation keys
- Ensure INI files are valid and parse correctly

### JSON
- Use consistent formatting with 2-space indentation
- Validate JSON against schemas when possible
- Provide meaningful error messages for JSON parsing failures
- Use consistent structure for API responses
- Include locale information in configuration files

### Testing Standards
- Use PHPUnit for PHP testing with proper test isolation
- Use Codeception for acceptance and functional testing
- Use descriptive test method names that explain the scenario
- Use data providers for testing multiple scenarios
- Mock external dependencies and services appropriately
- Use specific assertions and provide clear failure messages
