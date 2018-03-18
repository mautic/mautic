Mautic Introduction
===========
![Mautic](https://www.mautic.org/media/images/github_readme.png "Mautic Open Source Marketing Automation")

## Getting Started

The GitHub version is recommended for development or testing. Production package ready for install with all the libraries is at [https://www.mautic.org/download](https://www.mautic.org/download).

Documentation on how to use Mautic is available at [https://www.mautic.org/docs](https://www.mautic.org/docs).

This is a simple 3 step installation process. You'll want to make sure you already have [Composer](http://getcomposer.org) available on your computer as this is a development release and you'll need to use Composer to download the vendor packages.

<table width="100%" border="0">
	<tr>
		<td>
			<center><b>Step 1</b></center>
		</td>
		<td>
			<center><b>Step 2</b></center>
		</td>
		<td>
			<center><b>Step 3</b></center>
		</td>
	</tr>
	<tr>
		<td align="center" width="33.3%">
			<a href="https://github.com/mautic/mautic/archive/master.zip">Download the repository zip</a><br />Extract this zip to your web root.
		</td>
		<td align="center" width="33.3%">
			Run the following command to install required packages.<br /> <code>composer install</code>
		</td>
		<td align="center" width="33.3%">
			Open your browser and complete the installation through the web installer.
		</td>
	</tr>
</table>

**Get stuck?** *No problem. Check out [general troubleshooting](https://mautic.org/docs/en/tips/troubleshooting.html) and if it won't solve your issue join us at the <a href="https://www.mautic.org/community">Mautic community</a> for help and answers.*

## Disclaimer
Installing from source is only recommended if you are comfortable using the command line. You'll be required to use various CLI commands to get Mautic working and to keep it working. If the source and/or database schema gets out of sync with Mautic's releases, the release updater may not work and will require manual updates. For production the pre-packaged Mautic available at [mautic.org/download](https://www.mautic.org/download) is recommended.

*Also note that the source outside <a href="https://github.com/mautic/mautic/releases">a tagged release</a> should be considered "alpha" and may contain bugs, cause unexpected results, data corruption or loss, and is not recommended for use in a production environment. Use at your own risk.*

## Requirements

#### Development / Build process requirements

1. Mautic uses Git as a version control system. Download and install git for your OS from https://git-scm.com/.
2. Install a server, PHP and MySql to be able to run Mautic locally. Easy option is [_AMP package for your OS](https://en.wikipedia.org/wiki/List_of_Apache%E2%80%93MySQL%E2%80%93PHP_packages).
3. Install [Composer](https://getcomposer.org/), the dependency manager for PHP.
4. Install [NPM](https://www.npmjs.com/).
5. Install [Grunt](http://gruntjs.com/).

#### Mautic requirements

1. See [Mautic requirements](https://www.mautic.org/download/requirements).
2. PHP modules:
	- required: `zip`, `xml`, `mcrypt`, `imap`, `mailparse`
	- recommended: `openssl`, `opcache` / `apcu` / `memcached`
	- recommended for development: `xdebug`
3. Recommended memory limit: minimally 256 MB for testing, 512 MB and more for production.

## Installation

1. Open a Terminal/Console window.
2. Change directory to the server root (i.e. `cd /var/www` if your local server root is at /var/www).
3. Clone the repository (`git clone https://github.com/mautic/mautic.git`)
4. The **mautic** directory should appear in the server root. Change directory to mautic directory (`cd mautic`).
5. Install dependencies (`composer install`).
6. Visit Mautic in a browser (probably at http://localhost/mautic) and follow installation steps.

## Keeping Up-To-Date

### Source Files

Each time you update Mautic's source after the initial setup/installation via a new checkout, download, git pull, etc; you will need to clear the cache. To do so, run the following command:

    $ cd /your/mautic/directory
    $ php app/console cache:clear

(Note that if you are accessing Mautic through the dev environment (via index_dev.php), you would need to add the <code>--env=dev</code> from the command).

### Vendors

Run `composer install` to ensure new vendors are installed and/or existing upgraded.

### Database Schema

Before running these commands, please make a backup of your database.

If updating from <a href="https://github.com/mautic/mautic/releases">a tagged release</a> to <a href="https://github.com/mautic/mautic/releases">a tagged release</a>, schema changes will be included in a migrations file. To apply the changes, run

    $ php app/console doctrine:migrations:migrate

If you are updating to the latest source (remember this is alpha), first run

    $ php app/console doctrine:schema:update --dump-sql

This will list out the queries Doctrine wants to execute in order to get the schema up-to-date (no queries are actually executed). Review the queries to ensure there is nothing detrimental to your data. If you have doubts about a query, submit an issue here and we'll verify it.

If you're satisfied with the queries, execute them with

    $ php app/console doctrine:schema:update --force

Your schema should now be up-to-date with the source.

## Development environment

Mautic downloaded from GitHub has the development environment. You can access it by adding `index_dev.php` after the Mautic URL. Eg. `http://localhost/mautic/index_dev.php/s/`. Or in case of CLI commands, add `--env=dev` attribute to it.

This development environment will display the PHP errors, warnings and notices directly as the output so you don't have to open the log to see them. It will also load for example translations without cache, so every change you make will be visible without clearing it. The only changes which require clearing the cache are in the `config.php` files.

In case of assets like JS, CSS, the source files are loaded instead of concatenated, minified files. This way the changes in those files will be directly visible on refresh. If you'd wanted to see the change in the production environment, you'd have to have run the `app/console mautic:assets:generate` command.

In many cases, the CSS files are built from LESS files. To compile the changes in the LESS files, run `grunt compile-less` command.

## Testing

### Pull Request Testing

Everyone can test submitted features and bug fixes. No programming skills are required. All you have to do is to follow the steps below.

Every change to Mautic core happens via PRs. Every PR must have 2 successful tests to be merged to the core and released in the next version. Testing a PR is a great way to move Mautic forward and personally improve its quality and stability.

1. [Select a PR](https://github.com/mautic/mautic/pulls) to test.
2. Read the description and steps to test. If it's a bug fix, follow the steps to ensure you can recreate the issue.
3. Use the development environment (above) for testing.
3. [Apply the PR](https://help.github.com/articles/checking-out-pull-requests-locally/#modifying-an-inactive-pull-request-locally)
4. Clear cache for development environment (`rm -rf app/cache/*` or `app/console cache:clear -e dev`).
5. Follow the steps from the PR description again to see if the result is as described.
6. Write a comment about how the test went. If there is a problem, provide as much information as possible including error log messages.

### Automated Testing

Mautic uses [Codeception](https://codeception.com), [PHPUnit](https://phpunit.de), and [Selenium](http://www.seleniumhq.org)
as our suite of testing tools. 

#### PHPUnit

Before executing unit tests, copy the `.env.dist` file to `.env` then update to reflect your local environment
configuration.

**Running functional tests without setting the .env file with a different database will result in the configured database being overwritten.**

To run the entire test suite: 

```bash
bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist
```

To run tests for a specific bundle:
```bash
bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist --filter EmailBundle
```

To run a specific test:
```bash
bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist --filter "/::testVariantEmailWeightsAreAppropriateForMultipleContacts( .*)?$/" Mautic\EmailBundle\Tests\EmailModelTest app/bundles/EmailBundle/Tests/Model/EmailModelTest.php
```

#### Codeception/Selenium

If you plan on running the acceptance test suite, you'll need to have the Selenium Server Standalone installed and the
Chrome WebDriver available locally.

##### Mac OS

If you're on a Mac and you use [Homebrew](https://brew.sh), you can install Selenium by running `brew install selenium-server-standalone`.
You'll also need to download the latest [Chrome WebDriver](https://sites.google.com/a/chromium.org/chromedriver/downloads).
Unzip and move the `chromedriver` file to `/usr/local/Cellar/selenium-server-standalone/drivers/chromedriver`.
Once you have Selenium installed and the WebDriver available at the specified location, open and modify the plist file found at `/usr/local/Cellar/selenium-server-standalone/3.5.3/homebrew.mxcl.selenium-server-standalone.plist`.
In the `<dict><array>` block under `ProgramArguments`, add the following after the line containing `<string>-jar</string>`"

```xml
...
<string>-Dwebdriver.chrome.driver=/usr/local/Cellar/selenium-server-standalone/drivers/chromedriver</string>
...
```

With that completed, you may now start the Selenium server using `brew services start selenium-server-standalone`.

##### Other Platforms

Follow the standard installation procedure for Selenium server standalone. Ensure that you have the chrome driver
available, and startup the server with the following command:

```sh
java -jar -Dwebdriver.chrome.driver=/path/to/chromedriver /full/path/to/selenium-server-standalone.3.x.x.jar
```

##### Executing Tests

All test suites can be executed by running `bin/codecept run` from the project root. Optionally, you can specify
running just the `acceptance`, `functional`, or `unit` test suites by adding one of those words after the `run` command.

### Static Analysis

Mautic uses [PHPSTAN](https://github.com/phpstan/phpstan) for some of its parts during continuous integration tests. If you want to test your specific contribution locally, install PHPSTAN globally with `composer global require phpstan/phpstan-shim`. Mautic cannot have PHPSTAN as its dev dependency, because it requires PHP7+. To run analysis on a specific bundle, run `~/.composer/vendor/phpstan/phpstan-shim/phpstan.phar analyse app/bundles/*Bundle`

## FAQ and Contact Information
Marketing automation has historically been a difficult tool to implement in a business. The Mautic community is a rich environment for you to learn from others and share your knowledge as well. Open source means more than open code. Open source is providing equality for all and a chance to improve. If you have questions then the Mautic community can help provide the answers.

**Ready to get started with the community?** You can get <a href="https://www.mautic.org/get-involved">more involved</a> on the <a href="https://www.mautic.org">Mautic</a> website. Or follow Mautic on social media just to stay current with what's happening!

### Contact Info

* <a href="https://www.mautic.org">https://www.mautic.org</a>
* <a href="https://twitter.com/MauticCommunity">@MauticCommunity</a> [Twitter]
* <a href="https://www.facebook.com/MauticCommunity/">@MauticCommunity</a> [Facebook]
* <a href="https://plus.google.com/+MauticOrg">+MauticOrg</a> [Google+]

### Developers

We love testing our user interface on as many platforms as possible (even those browsers we prefer to not mention). In order to help us do this we use and recommend BrowserStack.
[<img src="https://www.mautic.org/media/browserstack_small.png" />](https://www.browserstack.com/)
