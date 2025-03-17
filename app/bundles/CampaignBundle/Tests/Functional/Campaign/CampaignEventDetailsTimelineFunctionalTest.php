<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignEventDetailsTimelineFunctionalTest extends MauticMysqlTestCase
{
    use CampaignEntitiesTrait;

    protected $useCleanupRollback = false;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function testCampaignEventDetailsForContactFieldValueDecision(): void
    {
        $object       = 'lead';
        $fieldDetails = [
            'alias'      => 'select_field',
            'type'       => 'select',
            'group'      => 'core',
            'object'     => $object,
            'properties' => [
                'list' => [
                    ['label' => 'l1', 'value' => 'v1'],
                    ['label' => 'l2', 'value' => 'v2'],
                    ['label' => 'l3', 'value' => 'v3'],
                    ['label' => 'l4', 'value' => 'v4'],
                    ['label' => 'l5', 'value' => 'v5'],
                ],
            ],
        ];
        $this->makeField($fieldDetails);

        $segment  = $this->createSegment('seg1', []);
        $lead1    = $this->createLeadData($segment, $object, $fieldDetails, ['v1'], 1); // yes path
        $lead2    = $this->createLeadData($segment, $object, $fieldDetails, ['v2'], 2); // no path
        $campaign = $this->createCampaign('c1', $segment);

        $parentEvent = $this->createEvent('Field Value Condition', $campaign,
            'lead.field_value',
            'condition',
            [
                'field'    => $fieldDetails['alias'],
                'operator' => 'in',
                'value'    => [
                    'v1', 'v3',
                ],
            ]
        );

        $this->createEvent('Add 10 points', $campaign,
            'lead.changepoints',
            'action',
            ['points' => 10],
            'yes',
            $parentEvent
        );

        $this->createEvent('Add 5 points', $campaign,
            'lead.changepoints',
            'action',
            ['points' => 5],
            'no',
            $parentEvent
        );

        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        $translator = static::getContainer()->get('translator');
        \assert($translator instanceof TranslatorInterface);

        $this->client->request('GET', sprintf('/s/contacts/view/%s', $lead1->getId()));
        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.parent.details', ['%path%' => 'yes', '%type%' => 'condition', '%name%' => 'Field Value Condition']),
            $this->client->getResponse()->getContent()
        );

        $this->client->request('GET', sprintf('/s/contacts/view/%s', $lead2->getId()));
        $this->assertStringContainsString(
            $translator->trans('mautic.campaign.parent.details', ['%path%' => 'no', '%type%' => 'condition', '%name%' => 'Field Value Condition']),
            $this->client->getResponse()->getContent()
        );
    }
}
