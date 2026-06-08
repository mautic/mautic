<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Model;

use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\LeadField;

class LeadModelSelectFieldTrimTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testCampaignEventValueIsNormalizedWhenCustomFieldTrailingSpacesChanged(): void
    {
        // Create custom field
        $field = new LeadField();
        $field->setLabel('Industry Type');
        $field->setAlias('industry_type');
        $field->setObject('lead');
        $field->setIsPublished(true);
        $field->setType('select');
        $field->setProperties([
            'list' => [
                ['label' => 'Automotive', 'value' => 'Automotive'],
                ['label' => 'Technology', 'value' => 'Technology'],
            ],
        ]);
        $this->em->persist($field);
        $this->em->flush();

        $campaign = $this->createCampaign('Industry Campaign');

        $event = $this->createEvent(
            'Update Industry',
            $campaign,
            'lead.update.field',
            'action',
            [
                'leadField' => 'industry_type',
                'value'     => 'Automotive',
            ]
        );

        $this->em->flush();
        $this->em->clear();

        // Modify custom field (add trailing spaces)
        $updatedField = $this->em->getRepository(LeadField::class)
          ->findOneBy(['alias' => 'industry_type']);

        $updatedField->setProperties([
            'list' => [
                ['label' => 'Automotive ', 'value' => 'Automotive '],
                ['label' => 'Technology ', 'value' => 'Technology '],
            ],
        ]);

        $this->em->persist($updatedField);
        $this->em->flush();
        $this->em->clear();

        /** @var EventModel $eventModel */
        $eventModel = $this->getContainer()->get('mautic.campaign.model.event');

        // Reload event after custom field change
        $eventEntity = $this->em->getRepository(CampaignEvent::class)
          ->findOneBy(['name' => 'Update Industry']);

        // Trigger normalization (your fix runs inside EventModel::saveEntity)
        $eventModel->saveEntity($eventEntity);

        $this->em->clear();

        // Validate result
        $reloadedEvent = $this->em->getRepository(CampaignEvent::class)
          ->findOneBy(['name' => 'Update Industry']);

        $props = $reloadedEvent->getProperties();

        $this->assertSame('Automotive', trim($props['value']));
    }
}
