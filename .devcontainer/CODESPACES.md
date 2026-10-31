# Mautic Codespaces

This is a fully functional development environment for Mautic running in your browser.

## Mautic Instance Is Initializing ...

Please wait until `CODESPACES-READY.md` shows up. Once that file exists you will be able to click and log in to your testing Mautic instance.

## Checkout a Specific Pull Request
1. In the left hand side toolbar you can see the Github Octocat logo.
2. Click on it to open the options in the left hand side bar.
3. Click on the 3 dots next to "PULL REQUESTS" heading.
4. Choose the "Checkout Pull Request by ID" option.
5. Insert the ID of the pull request you want to test.
6. The checkout will automatically install new dependencies if the PR changes them, rebuilds assets and clears the cache for you.

## Other tools in this development environment

Not only Mautic is installed and configured for you in this environment. There are other notable tools.

### Mailpit

No emails are being actually sent from this development environment if you are testing email sending. They all endup in Mailpit. So you can test the email sending and this environment won't be used for sending spam.

### PhpMyAdmin

If there is a pull request that needs you to check a database table then you can use PhpMyAdmin for that. It is a user interface that will show you all the tables and allow you to browse the data and execute raw SQL queries if you'll ever need that.

### CLI

This environment allows you to run all the Mautic command lines which can get handy if you need to emulate a cron job.

First you should get into the Mautic container by running

`ddev ssh`

Then you can execute all the commands you need.

### PHPUNIT/PHPSTAN/CSFIXER

You can use this environment for development if you don't want to spin up DDEV locally. You can run unit tests here or static analysis with PHPSTAN.

## Boot Speed Tips
1. Don't create the codespace directly from a pull request. It takes 12 minutes to spin up and it cannot use prebuilds.
2. Spin it up a Codespace directly from the default branch. It takes 4 minutes (3 times faster).
3. The workflow to spin up a codespace from 7.x has one more perk. You can have just 1 Codespace that takes 4 minutes to boot. But then you can test all the PRs you want to help with in this one codespace and just checkout the PR you are testing. The second boot of the sleeping Codespace is very fast. And the demo data you create will stay there. So way faster testing of all the PRs.
