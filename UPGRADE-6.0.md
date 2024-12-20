# Backwards compatibility breaking changes
- `Mautic\PointBundle\Form\Type\GenericPointSettingsType` was removed. See https://github.com/mautic/mautic/pull/13904
- Changes necessary for https://symfony.com/blog/new-in-symfony-5-3-guard-component-deprecation, see https://github.com/mautic/mautic/pull/14219
    - `Mautic\ApiBundle\DependencyInjection\Factory\ApiFactory` was removed.
    - The `friendsofsymfony/oauth-server-bundle` package was replaced with a maintained fork `klapaudius/oauth-server-bundle`
    - The `lightsaml/sp-bundle` package was replaced with a maintained fork `javer/sp-bundle`
- Deprecated `Mautic\LeadBundle\Model\FieldModel::getUniqueIdentiferFields` and `Mautic\LeadBundle\Model\FieldModel::getUniqueIdentifierFields` were removed. Use `Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier::getFieldsWithUniqueIdentifier` instead.
