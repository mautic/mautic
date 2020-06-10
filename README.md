# GrapesJS Builder with MJML support for Mautic

## Instalation

1. Apply PR https://github.com/mautic/mautic/pull/8892

2. Copy to plugin/IntegrationsBundle https://github.com/mautic-inc/plugin-integrations  +  PR https://github.com/mautic-inc/plugin-integrations/pull/76

3. Copy to plugins/GrapesJsBuilderBundle actual version of GrapeJS bundle https://github.com/Webmecanik/plugin-grapesjs-builder/archive/master.zip

4. Remove cache

## Setup

1. Got to plugins

2. Click to button Install/Upgrade Plugins

3. Find GrapeJs in plugin list and activate it

4. Now go to emails and try create/edit email

5. You should able to use GrapeJs editor for HTML emails and pages

## MJML support

GrapeJS plugin introduce also support for [MJML](https://mjml.io/) language. Just [create template](https://developer.mautic.org/#themes) as usual and add to template `email.mjml.tpl` file with properly MJML syntax.  

You can use our Blank MJML template to good start

[blank_mjml.zip](https://github.com/Webmecanik/plugin-grapesjs-builder/files/4757520/blank_mjml.zip)

## Support

Use Github issues for reporting and discuss more about this plugin

## Contribution

Create pull requests and we will review it soon as possible.

## Know issues

- Dynamic Content is not supported for MJML version of builder
- Edit button text by Delete/Backspace is not working
- Code mode editor is not working anymore

Contributions are welcome.

## Dependencies

This plugin use following GrapesJS plugin:

- Custom for Mautic : https://github.com/Webmecanik/grapesjs-preset-mautic
- Builder email MJML : https://github.com/artf/grapesjs-mjml
- Builder email HTML : https://github.com/artf/grapesjs-preset-newsletter
- Builder page HTML : https://github.com/artf/grapesjs-preset-webpage
- Parser CSS : https://github.com/artf/grapesjs-parser-postcss

If you need to update it, codes are in the `plguin/GrapesJsBuilderBundle/Assets/js` folder.