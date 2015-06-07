Mautic Introduction
===========
<img src="https://www.mautic.org/media/images/github_readme.png" />
<br /><br />
## Getting Started
<p>
	This is a simple 3 step installation process. You'll want to make sure you already have <a href="http://getcomposer.org">Composer</a> available on your computer as this is a development release and you'll need to use Composer to download the vendor packages. 
</p>
<table width="100%" border="0">
	<tr>
		<td>
			<center><strong>Step 1</strong></center>
		</td>
		<td>
			<center><strong>Step 2</strong></center>
		</td>
		<td>
			<center><strong>Step 3</strong></center>
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
<em>
	<strong>Get stuck?</strong> No problem. Check out the <a href="https://www.mautic.org/community">Mautic community</a> for help and answers.
</em>
<br />
<h2>Disclaimer</h2>
<p>Installing from source is only recommended if you are comfortable using the command line. You'll be required to use various CLI commands to get Mautic working and to keep it working. If the source and/or database schema gets out of sync with Mautic's releases, the release updater may not work and will require manual updates.</p>
<p><em>Also note that the source outside <a href="https://github.com/mautic/mautic/releases">a tagged release</a> should be considered "alpha" and may contain bugs, cause unexpected results, data corruption or loss, and is not recommended for use in a production environment. Use at your own risk.</em></p>
<p>If you prefer, there are packaged downloads ready for install at <a href="https://www.mautic.org/download">https://www.mautic.org/download</a>.</p>

<h2>Keeping Up-To-Date</h2>

<h3>Source Files</h3>

<p>Each time you update Mautic's source after the initial setup/installation via a new checkout, download, git pull, etc; you will need to clear the cache. To do so, run the following command:</p>
    
    $ cd /your/mautic/directory
    $ php app/console cache:clear --env=prod
    
<p>(Note that if you are accessing Mautic through the dev environment (via index_dev.php), you would need to drop the <code>--env=prod</code> from the command).</p>
  
<h3>Database Schema</h3>

<p>Before running these commands, please make a backup of your database.</p>
  
<p>If updating from <a href="https://github.com/mautic/mautic/releases">a tagged release</a> to <a href="https://github.com/mautic/mautic/releases">a tagged release</a>, schema changes will be included in a migrations file. To apply the changes, run</p>
  
    $ php app/console doctrine:migrations:migrate --env=prod
    
<p>If you are updating to the latest source (remember this is alpha), first run</p>
  
    $ php app/console doctrine:schema:update --env=prod --dump-sql
  
<p>This will list out the queries Doctrine wants to execute in order to get the schema up-to-date (no queries are actually executed). Review the queries to ensure there is nothing detrimental to your data. If you have doubts about a query, submit an issue here and we'll verify it.</p>
  
<p>If you're satisfied with the queries, execute them with</p>
  
    $ php app/console doctrine:schema:update --env=prod --force
  
<p>Your schema should now be up-to-date with the source.</p>
<h2>Usage</h2>
<p>
	Learning how to use marketing automation can be challenging. The first step is to understand what marketing automation is and how it can help your business be more successful. This quick usage outline is not meant to be comprehensive but will outline a few key areas of Mautic and how to use each of them.
</p>
<p><em>You can find more detailed information at <a href="https://docs.mautic.org">https://docs.mautic.org</a></em></p>

<h3>1. Monitoring</h3>

<p>The act of monitoring website traffic and visitors is often the first step in a marketing automation system. This step involves collecting details and information about each visitor to your website. </p>

<h4>Visitor Types</h4>
<p>There are two types of visitor, <strong>anonymous</strong> and <strong>known</strong>.</p>

<p><strong>Anonymous visitors</strong> are all visitors which browse to your website. These visitors are monitored and certain key pieces of information are collected. This information includes:</p>
<ul>
	<li>The visitor's IP address</li>
	<li>Country</li>
	<li>Pages visited</li>
	<li>Length of visit</li>
	<li>Any actions taken on site</li>
</ul>

<p><strong>Known visitors</strong> are visitors which have provided an email address or some other identifying characteristic (e.g. social network handle).  Much more information can be gathered from known visitors. Any information desired can be manually or automatically collected, here are just a few common ideas to get you started:</p>
<ul>
	<li>Email address</li>
	<li>Photos/images</li>
	<li>Physical address</li>
	<li>Social network handles</li>
	<li>Social media posts</li>
	<li>Notes</li>
	<li>Events</li>
</ul>
<p>These fields may be <em>automatically</em> discovered and added by the Mautic system or they may be manually added by you or the visitor.</p>

<h4>Visitor Transitions</h4>
<p>You will probably want to know how to move a visitor from anonymous to known status. This is critical as the amount of information collected from known visitors is much more in-depth and valuable to your business. You will learn more about this transition process a bit later on.
</p>

<h3>2. Connecting</h3>
<p>
	The next step in the marketing automation process is connecting with your known visitors. These known visitors are your leads (You may call your potential clients something different, for simplicity they are called leads in these docs). Connecting with your leads is important in establishing a relationship and nurturing them along the sales cycle. 
</p>
<p>
	This <strong>nurturing</strong> can be for any purpose you desire. You may wish to demonstrate your knowledge of a subject, encourage your leads to become involved, or generate a sale and become a customer.
</p>
<h4>Methods for Connecting</h4>
<p>
	There are several ways to connect with your leads. The three most common are <strong>emails</strong>, <strong>social media</strong>, and <strong>landing pages</strong>. 
</p>
<p><strong>Emails</strong> are by far the most common way to connect with leads. These are tracked and monitored by Mautic for who opens the email, what links are clicked within that email, and what emails bounce (never reach the recipient).</p>
<p><strong>Social media</strong> is quickly becoming a more popular way for connecting with leads. Mautic helps you monitor the social profiles of your leads and can be used to interact with them through their preferred network.</p>
<p><strong>Landing pages</strong> are usually the first step in the connection process as these are used to make initial contact with leads and collect the information to move them from an anonymous visitor to a known visitor. These pages are used to funnel visitors to a specific call to action. This call to action is usually a form to collect the visitor's information.</p>

<h3>3. Automating</h3>
<p>
	One of Mautic's main purposes is to enable automation of specific tasks. The task of connecting with leads is one such area where automation becomes increasingly valuable. Mautic allows you to define specific times, events, or actions when a connection should be triggered. Here is an example of an automation process.
</p>
<strong>Example</strong>
> A visitor fills out a call-to-action form on your landing page. This form collects their email address and automatically moves them from an <strong>anonymous</strong> to a <strong>known</strong> visitor. As a known visitor they are now added as a new lead to a specific campaign. This campaign will send the new lead an email you have pre-defined. You can then define additional actions to be taken based on the lead's response to your email.

<p>This example demonstrates several uses of automation. First, the visitor is <em>automatically</em> moved from anonymous to known status. Second, the visitor is <em>automatically</em> added to a particular campaign. Lastly the visitor is sent an email <em>automatically</em> as a new lead.</p>

<p>There are many more ways in which automation can be used throughout Mautic to improve efficiency and reduce the time you spend connecting with your leads. As mentioned earlier, refer to <a href="https://docs.mautic.org">https://docs.mautic.org</a> for more details. 
</p>

<h2>Customizing</h2>
<p>
	There are many benefits to using Mautic as your marketing automation tool. As the first and only community-driven, open source marketing automation platform there are many distinct advantages. If you are curious about those benefits and wish to read more about the value of choosing open source you can find more information on the <a href="https://www.mautic.org">Mautic</a> website. </p>
<p>
	One benefit of using Mautic is the ability to modify and customize the solution to fit your needs. Mautic allows you to quickly change to your preferred language, or modify any string through the language files. These language files are all stored on <a href="https://www.transifex.com/organization/mautic/dashboard/mautic">Transifex</a> and if you are interested you can add more translations.
</p>
<p>
	Customizations don't stop with the language strings. You can also construct workflows and campaigns to fit your business situation rather than adjusting your business to fit Mautic. The code is available and can be easily edited by any skilled developer. Below is some useful technical information regarding making changes.
</p>

<h3>Bundles</h3>
<p>Mautic has been configured to allow new features to be added by simply adding new bundles. These bundles can then be discovered by the software and installed from within Mautic. Each bundle contains the information for menus, routes, views, translations and more. These are self-contained objects but can interact with other bundles as necessary through triggers.</p>
<p><em>You can view the existing bundles in the GitHub repository <a href="https://github.com/mautic/mautic/tree/master/app/bundles">https://github.com/mautic/mautic/tree/master/app/bundles</a></em></p>

<h3>Templates</h3>
<p>Developers are also able to create customized templates to use within landing pages and emails. These are currently added directly through the <code>/themes</code> folder located in the root of the repository. Below is the structure of a template.</p>

<pre>
/YourThemeName
../css
../../style.css
../html
../../base.html.php
../../email.html.php
../../message.html.php
../../page.html.php
..config.php
</pre>
<p><em><strong>Required files</strong>: The only files required by Mautic for a theme is the config.php file. Every other file is optional. </em></p>

<h3>Workflows (Coming Soon)</h3>
<p>Another benefit to using Mautic, an open source platform, is the ability to share workflows and other helpful aspects of setting up a marketing automation implementation. These workflows may be the way a company handles campaigns, timing of emails, landing pages, assets, or other useful content. In a coming version of Mautic developers will be able to share these through the Mautic Marketplace&#0153;. This marketplace will provide a quick method for sharing, finding, and installing workflows.  
</p>

> <em>More detailed information regarding modifications and customizations as well as deeper tutorials on the above features can be found in the developer documentation when it is released.</em>

<h2>FAQ and Contact Information</h2>
<p>Marketing automation has historically been a difficult tool to implement in a business. The Mautic community is a rich environment for you to learn from others and share your knowledge as well. Open source means more than open code. Open source is providing equality for all and a chance to improve. If you have questions then the Mautic community can help provide the answers. </p>

<p><strong>Ready to get started with the community?</strong> You can get <a href="https://www.mautic.org/get-involved">more involved</a> on the <a href="https://www.mautic.org">Mautic</a> website. Or follow Mautic on social media just to stay current with what's happening!</p>

<h3>Contact Info</h3>
<ul>
	<li><a href="https://www.mautic.org">https://www.mautic.org</a></li>
	<li><a href="https://twitter.com/trymautic">@trymautic</a> [Twitter]</li>
	<li><a href="https://facebook.com/trymautic">@trymautic</a> [Facebook]</li>
	<li><a href="https://plus.google.com/+MauticOrg">+MauticOrg</a> [Google+]</li>
</ul>

<h3>Developers</h3>
<p>We love testing our user interface on as many platforms as possible (even those browsers we prefer to not mention). In order to help us do this we use and recommend BrowserStack.</p>
<img src="https://www.mautic.org/media/browserstack_small.png" />
