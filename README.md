# Mautic Integrations

Please note the following assumptions about this plugin:

1. This is being written to replace the dependency on the Mautic/PluginBundle/Integration/AbstractIntegration class.
2. It is not finished
3. Once finished, it will be contributed to core as a core bundle which will require plugins to migrate namespaces.
4. Mautic 2.14.0+ is required
5. PHP 7.x is currently required

## Install integrations bundle

1. Download https://github.com/mautic-inc/plugin-mautic-integrations/archive/master.zip
2. Extract it and rename `plugin-mautic-integrations-master` to `MauticIntegrationsBundle`
3. Move `MauticIntegrationsBundle` into Mautic's `plugins` folder (fix ownership if using something other than the web user)
4. Delete `app/cache/prod`