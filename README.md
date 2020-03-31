# GrapesJS Builder with MJML support for Mautic

## Instalation

Require:

1. This PR https://github.com/mautic/mautic/pull/7754

2. This plugin https://github.com/mautic-inc/plugin-integrations with this PR https://github.com/mautic-inc/plugin-integrations/pull/76

3. This bundle, install to plugins/GrapesJsBuilderBundle folder

Don't forget remove cache

## Setup

Setup is very easy. 

1. Just activate plugin.

## Dependencies

This plugin depend of following GrapesJS plugin:
- Custom for Mautic : https://github.com/Webmecanik/grapesjs-preset-mautic
- Builder email MJML : https://github.com/artf/grapesjs-mjml
- Builder email HTML : https://github.com/artf/grapesjs-preset-newsletter
- Builder page HTML : https://github.com/artf/grapesjs-preset-webpage
- Parser CSS : https://github.com/artf/grapesjs-parser-postcss

If you need to update it, code is in the `Assets/js` folder.
