# Mautic Integrations

Please note the following assumptions about this plugin:

1. This is being written to replace the dependency on the Mautic/PluginBundle/Integration/AbstractIntegration class.
2. It is not finished
3. Once finished, it will be contributed to core as a core bundle which will require plugins to migrate namespaces.

## Requirements

1. Mautic 2.14+
2. PHP 7+

## Installation

1. Download https://github.com/mautic-inc/plugin-mautic-integrations/archive/master.zip
2. Extract it to `plugins/MauticIntegrationsBundle`
4. Delete `app/cache/prod`