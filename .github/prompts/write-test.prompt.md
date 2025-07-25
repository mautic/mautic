---
mode: 'agent'
tools: ['testFailure', 'usages']
description: 'Generate a functional test for highlighted code'
---
Generate a new or update an existing functional test for the highlighted code. The test should cover the following aspects:

A good test should be:
- **Isolated**: It should not depend on other tests or external systems.
- **Fast**: It should run quickly to allow for rapid feedback during development.
- **Deterministic**: It should produce the same result every time it runs, regardless of the environment.
- **Readable**: It should be easy to understand and maintain.
- **Descriptive**: It should clearly indicate what it is testing and why.
- **Comprehensive**: It should cover a wide range of scenarios, including edge cases.
- **Independent**: It should not rely on the state of the application or database, ensuring that it can run in any order without affecting other tests.
- **Behavior-focused**: It should test output for specific input rather than internal implementation, except when verifying performance-critical operations like expensive method calls.

Good practices:
- Never mock objects that have no PHP service dependencies like event or entity classes. Use the real object instead.
- The best way to write a functional test is to use the actual endpoint. Either call a route or a command. The next best thing is to call a subscriber via EventDispatcher. If that is still too broad call a service.
- Make all new classes final by default. Including tests.
- Provide property, param and return types. If not possible to use native types you can always specify the types in the docblock. Mautic uses PHPSTAN so be sure to add types so the PHPSTAN won't fail.
- Use `$this->assertResponseIsSuccessful();` to assert successful requests.
- Use PHPUNIT's data providers to test multiple scenarios in a single test method.

Suggestions for AI:
- Do not modify the production code unless requested. Always just modify the test code.
- Make the simplest test possible. The human will suggest improvements if needed.

You can take an inspiration of existing functional tests. They all extend the `MysqlFunctionalTestCase` class and are located in the `app/bundles/*Bundle/Tests` or `plugins/*Bundle/Tests` directory. The tests are written in PHPUNIT. You can read the version in the `composer.json` file.

To execute a test you have to run it in DDEV. Example: `ddev exec composer test app/bundles/ExampleBundle/tests/ExampleTest.php`.