<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\PointInsight;
use Mautic\PointBundle\Model\PointGroupModel;

class PointInsightsFunctionalTest extends MauticMysqlTestCase
{
    private const GROUP_A_SUFFIX = ' (Group A)';

    protected $useCleanupRollback = false;

    public function testPointInsightExecutionWithSingleWinner(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');

        /** @var PointGroupModel $pointGroupModel */
        $pointGroupModel = self::getContainer()->get('mautic.point.model.group');

        $groupA      = $this->createGroup('Group A');
        $groupB      = $this->createGroup('Group B');
        $contact     = $this->createContact('winner@example.com');
        $customField = $this->createCustomField('winner_group');

        $this->createPointInsight(
            'Single Winner Test',
            [$groupA->getId(), $groupB->getId()],
            $customField->getAlias()
        );

        $this->em->flush();

        $pointGroupModel->adjustPoints($contact, $groupA, 10);
        $pointGroupModel->adjustPoints($contact, $groupB, 5);

        $this->em->clear();
        $contact = $leadModel->getEntity($contact->getId());

        $expectedValue = $groupA->getId().self::GROUP_A_SUFFIX;
        $this->assertEquals($expectedValue, $contact->getFieldValue($customField->getAlias()));
    }

    public function testPointInsightExecutionWithTieBreaker(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');

        /** @var PointGroupModel $pointGroupModel */
        $pointGroupModel = self::getContainer()->get('mautic.point.model.group');

        $groupA      = $this->createGroup('Group A');
        $groupB      = $this->createGroup('Group B');
        $groupC      = $this->createGroup('Group C');
        $contact     = $this->createContact('tiebreaker@example.com');
        $customField = $this->createCustomField('tie_winner');

        $this->createPointInsight(
            'Tie Breaker Test',
            [$groupA->getId(), $groupB->getId(), $groupC->getId()],
            $customField->getAlias()
        );

        $this->em->flush();

        $pointGroupModel->adjustPoints($contact, $groupA, 10);
        $pointGroupModel->adjustPoints($contact, $groupB, 5);
        $pointGroupModel->adjustPoints($contact, $groupC, 10);

        $this->em->clear();
        $contact = $leadModel->getEntity($contact->getId());

        $winnerGroupName = $contact->getFieldValue($customField->getAlias());

        $expectedValues = [
            $groupA->getId().self::GROUP_A_SUFFIX,
            $groupC->getId().' (Group C)',
        ];
        $this->assertContains($winnerGroupName, $expectedValues);

        if ($groupA->getId() < $groupC->getId()) {
            $this->assertEquals($groupA->getId().self::GROUP_A_SUFFIX, $winnerGroupName);
        } else {
            $this->assertEquals($groupC->getId().' (Group C)', $winnerGroupName);
        }
    }

    public function testPointInsightExecutionForMultipleContacts(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');

        /** @var PointGroupModel $pointGroupModel */
        $pointGroupModel = self::getContainer()->get('mautic.point.model.group');

        $groupA      = $this->createGroup('Multi Group A');
        $groupB      = $this->createGroup('Multi Group B');
        $customField = $this->createCustomField('multi_winner');

        $this->createPointInsight(
            'Multiple Contacts Test',
            [$groupA->getId(), $groupB->getId()],
            $customField->getAlias()
        );

        $contact1 = $this->createContact('multi1@example.com');
        $contact2 = $this->createContact('multi2@example.com');
        $contact3 = $this->createContact('multi3@example.com');

        $this->em->flush();

        $pointGroupModel->adjustPoints($contact1, $groupA, 20);
        $pointGroupModel->adjustPoints($contact1, $groupB, 10);

        $pointGroupModel->adjustPoints($contact2, $groupA, 5);
        $pointGroupModel->adjustPoints($contact2, $groupB, 15);

        $pointGroupModel->adjustPoints($contact3, $groupA, 12);
        $pointGroupModel->adjustPoints($contact3, $groupB, 12);

        $this->em->clear();
        $contact1 = $leadModel->getEntity($contact1->getId());
        $contact2 = $leadModel->getEntity($contact2->getId());
        $contact3 = $leadModel->getEntity($contact3->getId());

        $this->assertEquals($groupA->getId().' (Multi Group A)', $contact1->getFieldValue($customField->getAlias()));
        $this->assertEquals($groupB->getId().' (Multi Group B)', $contact2->getFieldValue($customField->getAlias()));

        $winnerName = $contact3->getFieldValue($customField->getAlias());
        if ($groupA->getId() < $groupB->getId()) {
            $this->assertEquals($groupA->getId().' (Multi Group A)', $winnerName);
        } else {
            $this->assertEquals($groupB->getId().' (Multi Group B)', $winnerName);
        }
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    private function createGroup(string $name): Group
    {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }

    /**
     * @param array<int> $pointGroupIds
     */
    private function createPointInsight(string $name, array $pointGroupIds, string $customFieldAlias): PointInsight
    {
        $insight = new PointInsight();
        $insight->setName($name);
        $insight->setDescription('Functional test insight');
        $insight->setInsightType(PointInsight::INSIGHT_TYPE_COMPARE_POINT_GROUPS);
        $insight->setInsightAction(PointInsight::INSIGHT_ACTION_SET_CUSTOM_FIELD);
        $insight->setCustomField($customFieldAlias);
        $insight->setPointGroups($pointGroupIds);
        $insight->setIsPublished(true);

        $this->em->persist($insight);

        return $insight;
    }

    private function createCustomField(string $alias): LeadField
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = self::getContainer()->get('mautic.lead.model.field');

        $field = new LeadField();
        $field->setLabel(ucfirst(str_replace('_', ' ', $alias)));
        $field->setAlias($alias);
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setIsPublished(true);

        $fieldModel->saveEntity($field);

        return $field;
    }
}
