# GrapesJS Builder with MJML support for Mautic

## This plugin is now managed centrally in https://github.com/mautic/mautic and https://github.com/mautic/plugin-grapesjs-builder will become a read-only repository.

**📣 Please make PRs and issues against Mautic Core, not here!**

⚠️ This Plugin is still Beta! It works great already and we're developing it actively! Please use it and report everything inside the "Issues" in https://github.com/mautic/mautic/issues. ⚠️

## Installation in Mautic 3.x

Enable the plugin in Mautic.

via SSH:
```
sudo cd /path/to/mautic
sudo php bin/console mautic:plugins:update
```
or directly in Mautic:

Go to the Plugins tab in Mautic:
» Click on the cogwheel (Settings) on the right hand top corner » Go to Plugins » Click on Update Plugins on the right hand top corner

Now activate the Plugin

Head over to the Plugins tab in Mautic:
» Click on the cogwheel (Settings) on the right hand top corner » Go to Plugins » Find the Plugin called "GrapesJs" and click on it » Click "yes" in the modal popup » Click the "Save and Close" button

7. Clear Cache again
```
sudo cd /path/to/mautic
sudo php bin/console cache:clear
```

Ready to go. Now the GrapesJs Builder is loaded for Emails and Landing pages. 

-------------------------------------------------------------------

## MJML support

GrapesJS plugin introduce also support for [MJML](https://mjml.io/) language. Just [create template](https://developer.mautic.org/#themes) as usual and add to template `email.mjml.tpl` file with properly formatted MJML syntax.  

You can use the Blank template as a starting point - this ships with Mautic.

## Existing Templates

**If you want to use any of your existing themes with the new builder, add the following to your theme's configuration file:**

Read more in the documentation here: https://docs.mautic.org/en/builders

## Support

Use Github issues at https://github.com/mautic/mautic/issues for reporting and discuss more about this plugin. Please join #i-builders on Slack if you would like to get involved in supporting, maintaining and improving the builders! Get your Slack invite at [https://mautic.org/slack](https://mautic.org/slack)

## Contribution

Create Pull Requests and we will review them soon as possible.

## Dependencies

This plugin use following GrapesJS plugins and preset:

- Custom for Mautic : https://github.com/mautic/grapesjs-preset-mautic
- Builder email MJML : https://github.com/artf/grapesjs-mjml
- Builder email HTML : https://github.com/artf/grapesjs-preset-newsletter
- Builder page HTML : https://github.com/artf/grapesjs-preset-webpage
- Parser CSS : https://github.com/artf/grapesjs-parser-postcss

If you want to update it, the code can be found in the `plugin/GrapesJsBuilderBundle/Assets/js` folder.

-------------------------------------------------------------------

## Switching back to the legacy Builder

In case you are not happy with the plugin at the moment, you can easily switch back to the legacy Builder (original Mautic builder). You can do so very quickly:
1. Go to Mautic Settings » Click the cogwheel on the right-hand top corner 
2. Open the Plugins Directory » click on "Plugins" inside the menu
3. Find the GrapesJs Plugin and click on it » Click "No" and then "Save and Close"
4. Clear the cache

Mautic unloaded the GrapesJs Plugin and uses the legacy builder again.
-------------------------------------------------------------------

## Code
### Setup 
```bash
npm install
```

#### Configure babel, eslint, prettier
use the template files provided. E.g. .eslintrc.temp

### How to develop and test Grapesjs standalone
```bash
# for the bare grapesjs editor
npm run start-helloWorld

# or for the light mautic experience (no dynamic content)
npm run start-mautic

# or for the full mautic experience
npm run start-mautic-full
```

#### Hints
- In order for start-mautic* to work a running ddev container has to be present. 
- If you are on some other development environment you need to update some paths in the html files. E.g. Demo/mautic/index.html
- Tell Chrome to ignore cors issues: ?open -n -a /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --args --user-data-dir="/tmp/chrome_dev_test" --disable-web-security?

### How to build for production
```bash
npm run build
```

## Code Architecture

There is the JS code in the Assets/libarary folder. This handles the bootstrapping and management. 

In addition there is the mautic preset. This handles the basic Mautic specific code. The general idea is that this preset repo can be used as a base for various Mautic builder plugins. E.g one where the RTE ediotor is the CKEditor, or where we have some very customer specific functionality.
```
- It's a pack of configurable feautures:
- Adds the function to edit source code
- Extends the original image and add a confirm dialog before removing it
- Add the option to hide/show the Layers Manager
- Add the option to enable/disable the import code button
- Moves the Settings panel inside Style Manager panel
- Opens the Block Manager at launch
- Replace Rich Text Editor by Froala used in Mautic (add token support)
- Add Dynamic Content Block for HTML used in Mautic
```

## Sources
[New builder documentation resources](https://docs.mautic.org/en/builders)
