# GrapesJS Builder with MJML support for Mautic

⚠️ This Plugin is still Beta! It works great already and we're developing it actively! Please use it and report everything inside the "Issues" here in Github. ⚠️

## Installation in Mautic 3.x

1. Navigate to the plugins directory of your Mautic installation via SSH/FTP/SFTP
Here you find the SSH Commands to install the plugin (with FTP simply download and drag'n'drop the folders to the right directory)
```
cd /path/to/mautic/plugins
```
(path/to/mautic needs to be the correct path where your Mautic is installed. This varies for each installation. It is most likely something like /var/www/html/mautic )

2. Download the plugin directly to the plugins folder on your server.
```
sudo wget https://github.com/mautic/plugin-grapesjs-builder/archive/mautic3x.zip
```
3. Unzip the content 
```
sudo unzip mautic3x.zip
```

4. Move the content of the package to a folder called GrapesJsBuilderBundle
```
sudo mv plugin-grapesjs-builder-mautic3x  GrapesJsBuilderBundle
```

5. Clear Mautic Cache
```
sudo cd /path/to/mautic
sudo php bin/console cache:clear
```
6. Install the plugin in Mautic.

via SSH:
```
sudo cd /path/to/mautic
sudo php bin/console mautic:plugins:update
```
or directly in Mautic:

Go to the Plugins tab in Mautic:
» Click on the cogwheel (Settings) on the right hand top corner » Go to Plugins » Click on Update Plugins on the right hand top corner

6. Now activate the Plugin

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

You can use the Blank MJML template provided by Webmecanik Blank MJML as a starting point.

[blank_mjml.zip](https://github.com/mautic/plugin-grapesjs-builder/files/4757520/blank_mjml.zip)

## Support

Use Github issues for reporting and discuss more about this plugin

## Contribution

Create Pull Requests and we will review them soon as possible.

## Dependencies

This plugin use following GrapesJS plugin:

- Custom for Mautic : https://github.com/Webmecanik/grapesjs-preset-mautic
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

## Support for Mautic 2.x

We are going to delete the branch for the Mautic 2.x Plugin on February 28th 2021. 
If you feel the need to develop the existing 2.x branch further, please fork it and develop it on your own repo. 
Here is the link to the 2.x Branch with the work done so far: 
https://github.com/mautic/plugin-grapesjs-builder/tree/master 

-------------------------------------------------------------------

## Code
### Setup 
```bash
npm install
```

#### Configure babel, eslint, prettier
use the template files provided. E.g. .eslintrc.temp

### How to test standalone
```bash
npm run mautic
```

### How to build for production
```bash
npm run build
```

## Sources
[New builder documentation resources](https://docs.google.com/document/d/1gdyojOM-K-Otk2iPo92qennjw3yKvdd6VUjToGzFgC0/edit#heading=h.akyer7a3p06t)
