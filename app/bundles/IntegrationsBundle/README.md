# Mautic Integrations

This bundle allows you to create integrations with CRM systems with support for 2-way sync between Mautic and the CRM.

### Sync command

`$ bin/console mautic:integrations:sync Magento --first-time-sync --start-datetime="2019-09-12T12:00:00"`

This is how you should use it when you configure an integration (Magento in this case) and run the sync for the first time. Specify also from what date it should look for the entities to sync. This way you can controll how big batch of records you will sync with one command. If you want to sync with multiple chunks by date ranges, `--end-datetime` option will be helpful too.

The sync command in basic use looks like this:

`$ bin/console mautic:integrations:sync Magento`

It will sync all new records from and to Mautic for Magento. There is no need to specify the date range as Mautic is smart enough to read the start date from the records it has already synchronized. And the end date is "now".

`$ bin/console mautic:integrations:sync Magento --disable-pull --mautic-object-id=contact:12 --mautic-object-id=contact:13`

There is also option to force sync of specific objects. With the `--disable-pull` flag the sync will skip the pull process. If some `--mautic-object-id` options are set it will not sync by a date range but rather only the IDs you will specify. `--disable-push` only disables the push. Pulling specific records by ID is not implemented yet.

The format of the `--mautic-object-id` values is `object type[colon]object ID`. Mautic can sync 2 object types: `contact` and `company`. The latter is not implemented yet.

The `--integration-object-id` uses the same format as `--mautic-object-id` but it's up to each integration to support it.

Similarly, you can push specific Mautic contacts to the integration you are developing like the following example. It can be useful if you want to push as a campaign/form/point action.

```php
$mauticObjectIds = new \Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO();
$mauticObjectIds->addObjectId('contact', '12');
$mauticObjectIds->addObjectId('contact', '13');

$inputOptions = new Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO(
    [
        'integration'      => 'Magento',
        'disable-pull'     => true,
        'mautic-object-id' => $mauticObjectIds,
    ]
);

/** @var \Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface $syncService **/
$syncService->processIntegrationSync($inputOptions);
```
