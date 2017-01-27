Mautic Introduction
===========
![Mautic](https://www.mautic.org/media/images/github_readme.png "Mautic Open Source Marketing Automation")

## Getting Started

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

**Get stuck?** *No problem. Check out the <a href="https://www.mautic.org/community">Mautic community</a> for help and answers.*

# Disclaimer
Installing from source is only recommended if you are comfortable using the command line. You'll be required to use various CLI commands to get Mautic working and to keep it working. If the source and/or database schema gets out of sync with Mautic's releases, the release updater may not work and will require manual updates.

*Also note that the source outside <a href="https://github.com/mautic/mautic/releases">a tagged release</a> should be considered "alpha" and may contain bugs, cause unexpected results, data corruption or loss, and is not recommended for use in a production environment. Use at your own risk.*

If you prefer, there are packaged downloads ready for install at [https://www.mautic.org/download](https://www.mautic.org/download).

# Keeping Up-To-Date

### Source Files

Each time you update Mautic's source after the initial setup/installation via a new checkout, download, git pull, etc; you will need to clear the cache. To do so, run the following command:

    $ cd /your/mautic/directory
    $ php app/console cache:clear --env=prod

(Note that if you are accessing Mautic through the dev environment (via index_dev.php), you would need to drop the <code>--env=prod</code> from the command).

### Database Schema

Before running these commands, please make a backup of your database.

If updating from <a href="https://github.com/mautic/mautic/releases">a tagged release</a> to <a href="https://github.com/mautic/mautic/releases">a tagged release</a>, schema changes will be included in a migrations file. To apply the changes, run

    `$ php app/console doctrine:migrations:migrate --env=prod`

If you are updating to the latest source (remember this is alpha), first run

    `$ php app/console doctrine:schema:update --env=prod --dump-sql`

This will list out the queries Doctrine wants to execute in order to get the schema up-to-date (no queries are actually executed). Review the queries to ensure there is nothing detrimental to your data. If you have doubts about a query, submit an issue here and we'll verify it.

If you're satisfied with the queries, execute them with

    `$ php app/console doctrine:schema:update --env=prod --force`

Your schema should now be up-to-date with the source.

# Usage

Learning how to use marketing automation can be challenging. The first step is to understand what marketing automation is and how it can help your business be more successful. This quick usage outline is not meant to be comprehensive but will outline a few key areas of Mautic and how to use each of them.  

*You can find more detailed information at <a href="https://docs.mautic.org">https://docs.mautic.org</a>*

### 1. Monitoring

The act of monitoring website traffic and visitors is often the first step in a marketing automation system. This step involves collecting details and information about each visitor to your website.  

#### Visitor Types
There are two types of visitor, **anonymous** and **known**.  

**Anonymous visitors** are all visitors which browse to your website. These visitors are monitored and certain key pieces of information are collected. This information includes:

* The visitor's IP address
* Country
* Pages visited
* Length of visit
* Any actions taken on site

**Known visitors** are visitors which have provided an email address or some other identifying characteristic (e.g. social network handle).  Much more information can be gathered from known visitors. Any information desired can be manually or automatically collected, here are just a few common ideas to get you started:

* Email address
* Photos/images
* Physical address
* Social network handles
* Social media posts
* Notes
* Events

These fields may be *automatically* discovered and added by the Mautic system or they may be manually added by you or the visitor.

#### Visitor Transitions

You will probably want to know how to move a visitor from anonymous to known status. This is critical as the amount of information collected from known visitors is much more in-depth and valuable to your business. You will learn more about this transition process a bit later on.

### 2. Connecting

The next step in the marketing automation process is connecting with your known visitors. These known visitors are your leads (You may call your potential clients something different, for simplicity they are called leads in these docs). Connecting with your leads is important in establishing a relationship and nurturing them along the sales cycle.

This **nurturing** can be for any purpose you desire. You may wish to demonstrate your knowledge of a subject, encourage your leads to become involved, or generate a sale and become a customer.

#### Methods for Connecting

There are several ways to connect with your leads. The three most common are **emails**, **social media**, and **landing pages**.

**Emails** are by far the most common way to connect with leads. These are tracked and monitored by Mautic for who opens the email, what links are clicked within that email, and what emails bounce (never reach the recipient).

**Social media** is quickly becoming a more popular way for connecting with leads. Mautic helps you monitor the social profiles of your leads and can be used to interact with them through their preferred network.

**Landing pages** are usually the first step in the connection process as these are used to make initial contact with leads and collect the information to move them from an anonymous visitor to a known visitor. These pages are used to funnel visitors to a specific call to action. This call to action is usually a form to collect the visitor's information.

###3. Automating

One of Mautic's main purposes is to enable automation of specific tasks. The task of connecting with leads is one such area where automation becomes increasingly valuable. Mautic allows you to define specific times, events, or actions when a connection should be triggered. Here is an example of an automation process.  

**Example**
A visitor fills out a call-to-action form on your landing page. This form collects their email address and automatically moves them from an **anonymous** to a **known** visitor. As a known visitor they are now added as a new lead to a specific campaign. This campaign will send the new lead an email you have pre-defined. You can then define additional actions to be taken based on the lead's response to your email.  

This example demonstrates several uses of automation. First, the visitor is *automatically* moved from anonymous to known status. Second, the visitor is *automatically* added to a particular campaign. Lastly the visitor is sent an email *automatically* as a new lead.  

There are many more ways in which automation can be used throughout Mautic to improve efficiency and reduce the time you spend connecting with your leads. As mentioned earlier, refer to [https://docs.mautic.org](https://docs.mautic.org) for more details.  

## Customizing

There are many benefits to using Mautic as your marketing automation tool. As the first and only community-driven, open source marketing automation platform there are many distinct advantages. If you are curious about those benefits and wish to read more about the value of choosing open source you can find more information on the [Mautic](https://www.mautic.org) website.  

One benefit of using Mautic is the ability to modify and customize the solution to fit your needs. Mautic allows you to quickly change to your preferred language, or modify any string through the language files. These language files are all stored on [Transifex](https://www.transifex.com/organization/mautic/dashboard/mautic) and if you are interested you can add more translations.  

Customizations don't stop with the language strings. You can also construct workflows and campaigns to fit your business situation rather than adjusting your business to fit Mautic. The code is available and can be easily edited by any skilled developer. Below is some useful technical information regarding making changes.

### Bundles

Mautic has been configured to allow new features to be added by simply adding new bundles. These bundles can then be discovered by the software and installed from within Mautic. Each bundle contains the information for menus, routes, views, translations and more. These are self-contained objects but can interact with other bundles as necessary through triggers.

*You can view the existing bundles in the GitHub repository [https://github.com/mautic/mautic/tree/master/app/bundles](https://github.com/mautic/mautic/tree/master/app/bundles)*

### Templates

Developers are also able to create customized templates to use within landing pages and emails. These are currently added directly through the `/themes` folder located in the root of the repository. Below is the structure of a template.


```
/YourThemeName
../css
../../style.css
../html
../../base.html.php
../../email.html.php
../../message.html.php
../../page.html.php
..config.php
```

**Required files**: *The only files required by Mautic for a theme is the config.php file. Every other file is optional.*

### Workflows (Coming Soon)

Another benefit to using Mautic, an open source platform, is the ability to share workflows and other helpful aspects of setting up a marketing automation implementation. These workflows may be the way a company handles campaigns, timing of emails, landing pages, assets, or other useful content. In a coming version of Mautic developers will be able to share these through the Mautic Marketplace&#0153;. This marketplace will provide a quick method for sharing, finding, and installing workflows.  


*More detailed information regarding modifications and customizations as well as deeper tutorials on the above features can be found in the developer documentation when it is released.*

## How to test a pull request

Everyone can test submitted features and bug fixes. No programming skills are required. All you have to do is to follow the steps below.

### Requirements

1. Mautic uses Git as a version control system. Download and install git for your OS from https://git-scm.com/.
2. Install a server, PHP and MySql/Posgres to be able to run Mautic locally. Easy option is [_AMP package for your OS](https://en.wikipedia.org/wiki/List_of_Apache%E2%80%93MySQL%E2%80%93PHP_packages).
3. Install [Composer](https://getcomposer.org/), the dependency manager for PHP.

### Install the latest GitHub version

1. Open a Terminal/Console window.
2. Change directory to the server root (i.e. `cd /var/www` if your local server root is at /var/www).
3. Clone the repository (`git clone https://github.com/mautic/mautic.git`)
4. The **mautic** directory should appear in the server root. Change directory to mautic directory (`cd mautic`).
5. Install dependencies (`composer install`).
6. Visit Mautic in a browser (probably at http://localhost/mautic) and follow installation steps.

### Test a pull request (PR)

1. [Select a PR](https://github.com/mautic/mautic/pulls) to test.
2. Read the description and steps to test. If it's a bug fix, follow the steps if you'll be able to recreate the issue.
3. Use development environment for testing. To do that, add `index_dev.php` after the Mautic URL. Eg. `http://localhost/mautic/index_dev.php/s/`
3. [Apply the PR](https://help.github.com/articles/checking-out-pull-requests-locally/#modifying-an-inactive-pull-request-locally)
4. Clear cache for development environment (`app/console cache:clear -e dev`).
5. Follow the steps from the PR description again to see if the result is as described.
6. Write a comment how the test went. If there is a problem, provide as many information as possible including error log messages.

## Unit Tests

The unit tests can be executed in the Mautic root directory with `phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist app/bundles` command.

# FAQ and Contact Information
Marketing automation has historically been a difficult tool to implement in a business. The Mautic community is a rich environment for you to learn from others and share your knowledge as well. Open source means more than open code. Open source is providing equality for all and a chance to improve. If you have questions then the Mautic community can help provide the answers.

**Ready to get started with the community?** You can get <a href="https://www.mautic.org/get-involved">more involved</a> on the <a href="https://www.mautic.org">Mautic</a> website. Or follow Mautic on social media just to stay current with what's happening!

### Contact Info

* <a href="https://www.mautic.org">https://www.mautic.org</a>
* <a href="https://twitter.com/mautic">@mautic</a> [Twitter]
* <a href="https://facebook.com/trymautic">@trymautic</a> [Facebook]
* <a href="https://plus.google.com/+MauticOrg">+MauticOrg</a> [Google+]

### Developers

We love testing our user interface on as many platforms as possible (even those browsers we prefer to not mention). In order to help us do this we use and recommend BrowserStack.
[<img src="https://www.mautic.org/media/browserstack_small.png" />](https://www.browserstack.com/)
