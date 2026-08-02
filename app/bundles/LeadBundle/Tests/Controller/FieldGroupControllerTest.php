<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldGroupModel;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Request;

final class FieldGroupControllerTest extends MauticMysqlTestCase
{
    // Tests create custom LeadFields (schema changes), which commit the
    // cleanup transaction; disable rollback so each test gets a fresh schema.
    protected $useCleanupRollback = false;

    private const INDEX_URL       = '/s/contacts/field-groups';

    private const ACTION_URL      = '/s/contacts/field-groups/%s/%s';

    private const SAVE_AND_CLOSE  = 'Save & Close';

    private FieldGroupModel $fieldGroupModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldGroupModel = self::getContainer()->get(FieldGroupModel::class);
    }

    private function createFieldGroup(string $name): FieldGroup
    {
        $group = new FieldGroup();
        $group->setName($name);
        $this->fieldGroupModel->saveEntity($group);

        return $group;
    }

    private function createLeadFieldInGroup(string $alias, string $groupAlias): LeadField
    {
        $fieldModel = self::getContainer()->get(FieldModel::class);

        $field = new LeadField();
        $field->setLabel(ucfirst($alias));
        $field->setAlias($alias);
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup($groupAlias);
        $fieldModel->saveEntity($field);

        return $field;
    }

    public function testIndexAction(): void
    {
        $this->createFieldGroup('Marketing Group');

        $crawler = $this->client->request(Request::METHOD_GET, self::INDEX_URL);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Marketing Group', $crawler->text());
    }

    public function testNewActionRendersForm(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, sprintf(self::ACTION_URL, 'new', ''));

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('input[name="field_group[name]"]')->count());
    }

    public function testNewActionCreatesGroupAndAutoGeneratesAlias(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, sprintf(self::ACTION_URL, 'new', ''));
        $form    = $crawler->selectButton(self::SAVE_AND_CLOSE)->form();
        $form['field_group[name]']->setValue('Sales Group');
        $form['field_group[description]']->setValue('A group for sales fields');
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $group = $this->fieldGroupModel->getRepository()->findOneBy(['name' => 'Sales Group']);
        $this->assertInstanceOf(FieldGroup::class, $group);
        $this->assertSame('sales_group', $group->getAlias());
    }

    public function testEditActionRendersForm(): void
    {
        $group   = $this->createFieldGroup('Editable Group');
        $crawler = $this->client->request(Request::METHOD_GET, sprintf(self::ACTION_URL, 'edit', $group->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSame('Editable Group', $crawler->filter('input[name="field_group[name]"]')->attr('value'));
    }

    public function testEditActionKeepsAliasImmutableOnRename(): void
    {
        $group         = $this->createFieldGroup('Original Name');
        $originalAlias = $group->getAlias();
        $this->assertSame('original_name', $originalAlias);

        $crawler = $this->client->request(Request::METHOD_GET, sprintf(self::ACTION_URL, 'edit', $group->getId()));
        $form    = $crawler->selectButton(self::SAVE_AND_CLOSE)->form();
        $form['field_group[name]']->setValue('Completely Renamed');
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $updated = $this->fieldGroupModel->getRepository()->find($group->getId());
        $this->assertInstanceOf(FieldGroup::class, $updated);
        $this->assertSame('Completely Renamed', $updated->getName());
        $this->assertSame($originalAlias, $updated->getAlias(), 'Alias must not change when the group is renamed.');
    }

    public function testDeleteActionRemovesEmptyGroup(): void
    {
        $group   = $this->createFieldGroup('Empty Group');
        $groupId = $group->getId();

        // deleteStandard only deletes on POST.
        $this->client->request(Request::METHOD_POST, sprintf(self::ACTION_URL, 'delete', $groupId));

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $this->assertNotInstanceOf(FieldGroup::class, $this->fieldGroupModel->getRepository()->find($groupId));
    }

    public function testDeleteActionIsBlockedWhenGroupHasFields(): void
    {
        $group = $this->createFieldGroup('Group With Fields');
        $this->createLeadFieldInGroup('group_with_fields_test', $group->getAlias());

        $groupId = $group->getId();

        $crawler = $this->client->request(Request::METHOD_POST, sprintf(self::ACTION_URL, 'delete', $groupId));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('still has fields assigned', $crawler->text());

        $this->em->clear();
        $this->assertInstanceOf(
            FieldGroup::class,
            $this->fieldGroupModel->getRepository()->find($groupId),
            'A group that still has fields must not be deleted.'
        );
    }

    public function testBatchDeleteActionRemovesEmptyGroups(): void
    {
        $group1 = $this->createFieldGroup('Batch One');
        $group2 = $this->createFieldGroup('Batch Two');
        $ids    = [$group1->getId(), $group2->getId()];

        // batchDeleteStandard reads ids from the query string on a POST.
        $this->client->request(
            Request::METHOD_POST,
            self::INDEX_URL.'/batchDelete?ids='.json_encode($ids)
        );

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $this->assertNotInstanceOf(FieldGroup::class, $this->fieldGroupModel->getRepository()->find($ids[0]));
        $this->assertNotInstanceOf(FieldGroup::class, $this->fieldGroupModel->getRepository()->find($ids[1]));
    }

    /**
     * Batch delete must skip groups that still have fields (it currently
     * delegates straight to batchDeleteStandard, which bypasses canDelete()).
     * Expected post-fix: the empty group is removed, the one with fields is kept.
     */
    public function testBatchDeleteActionSkipsGroupsWithFields(): void
    {
        $empty     = $this->createFieldGroup('Batch Empty');
        $withField = $this->createFieldGroup('Batch With Fields');
        $this->createLeadFieldInGroup('batch_guard_field', $withField->getAlias());

        $emptyId     = $empty->getId();
        $withFieldId = $withField->getId();

        $this->client->request(
            Request::METHOD_POST,
            self::INDEX_URL.'/batchDelete?ids='.json_encode([$emptyId, $withFieldId])
        );
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $repo = $this->fieldGroupModel->getRepository();
        $this->assertNotInstanceOf(FieldGroup::class, $repo->find($emptyId), 'Empty group should be batch-deleted.');
        $this->assertInstanceOf(
            FieldGroup::class,
            $repo->find($withFieldId),
            'A group that still has fields must not be batch-deleted.'
        );
    }

    public function testUnicodeNameIsTransliteratedToAsciiAlias(): void
    {
        $group = $this->createFieldGroup('Café Zürich');

        $this->assertSame('cafe_zurich', $group->getAlias());
    }

    public function testNonLatinNameStillGetsValidNonEmptyAlias(): void
    {
        $group = $this->createFieldGroup('日本語');

        $alias = $group->getAlias();
        $this->assertNotEmpty($alias, 'A non-Latin name must still yield a usable alias.');
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $alias);
    }

    public function testAliasCollisionGetsNumericSuffix(): void
    {
        $first  = $this->createFieldGroup('Café'); // -> cafe
        $second = $this->createFieldGroup('Cafe'); // -> cafe_1 (transliterates to the same base)

        $this->assertSame('cafe', $first->getAlias());
        $this->assertSame('cafe_1', $second->getAlias());
    }

    public function testNewGroupsGetIncrementingOrder(): void
    {
        $alpha = $this->createFieldGroup('Alpha');
        $beta  = $this->createFieldGroup('Beta');

        $this->assertGreaterThan(0, $alpha->getOrder());
        $this->assertSame($alpha->getOrder() + 1, $beta->getOrder(), 'Each new group must get the next order value.');
    }

    public function testSortGroupedFieldsFollowsGroupOrder(): void
    {
        $alpha = $this->createFieldGroup('Alpha'); // order 1
        $beta  = $this->createFieldGroup('Beta');  // order 2

        // Grouped fields keyed by alias, deliberately out of order.
        $grouped = [
            $beta->getAlias()  => ['x'],
            'core'             => ['y'],
            $alpha->getAlias() => ['z'],
        ];

        $sorted = array_keys($this->fieldGroupModel->sortGroupedFields($grouped, 'lead'));

        // Default 'core' first, then custom groups by their order (alpha, then beta).
        $this->assertSame(['core', $alpha->getAlias(), $beta->getAlias()], $sorted);
    }

    public function testEditActionReordersViaGroupOrderDropdown(): void
    {
        $alpha = $this->createFieldGroup('Alpha'); // order 1
        $beta  = $this->createFieldGroup('Beta');  // order 2
        $gamma = $this->createFieldGroup('Gamma'); // order 3

        // Edit Gamma and place it before Alpha via the "Group order" dropdown.
        $crawler = $this->client->request(Request::METHOD_GET, sprintf(self::ACTION_URL, 'edit', $gamma->getId()));
        $form    = $crawler->selectButton(self::SAVE_AND_CLOSE)->form();
        $form['field_group[order]']->setValue((string) $alpha->getId());
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $repo = $this->fieldGroupModel->getRepository();
        // Gamma now sits before Alpha; orders stay contiguous 1..n.
        $this->assertSame(1, $repo->find($gamma->getId())->getOrder());
        $this->assertSame(2, $repo->find($alpha->getId())->getOrder());
        $this->assertSame(3, $repo->find($beta->getId())->getOrder());
    }

    public function testSortGroupedFieldsAppendsUnknownGroups(): void
    {
        $alpha = $this->createFieldGroup('Alpha');

        // 'orphan' is neither a default nor a known custom group.
        $grouped = [
            'orphan'           => ['x'],
            'core'             => ['y'],
            $alpha->getAlias() => ['z'],
        ];

        $sorted = array_keys($this->fieldGroupModel->sortGroupedFields($grouped, 'lead'));

        // Known groups in configured order first, unknown groups appended last.
        $this->assertSame(['core', $alpha->getAlias(), 'orphan'], $sorted);
    }

    public function testFieldCountIsShownOnIndex(): void
    {
        $group = $this->createFieldGroup('Counted Group');
        $this->createLeadFieldInGroup('counted_group_field', $group->getAlias());

        $counts = $this->fieldGroupModel->getFieldCountPerGroup();
        $this->assertSame(1, $counts[$group->getAlias()] ?? 0);

        $crawler = $this->client->request(Request::METHOD_GET, self::INDEX_URL);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Counted Group', $crawler->text());
    }
}
