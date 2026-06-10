<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use PHPUnit\Framework\Assert;

class FocusRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private const FILTERS = [
        [
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '=',
            'filter'   => 'focus-filter@example.com',
            'display'  => null,
        ],
    ];

    public function testGetPublishedWithFiltersReturnsOnlyActiveFilterBasedItems(): void
    {
        $this->createFocus('Null filters', true);

        $emptyFilters = $this->createFocus('Empty filters', true);
        $emptyFilters->setFilters([]);

        $published = $this->createFocus('Published with filters', true);
        $published->setFilters(self::FILTERS);

        $unpublished = $this->createFocus('Unpublished with filters', false);
        $unpublished->setFilters(self::FILTERS);

        $publishUpInFuture = $this->createFocus('Publish up in future', true);
        $publishUpInFuture->setFilters(self::FILTERS);
        $publishUpInFuture->setPublishUp(new \DateTime('+1 day'));

        $publishDownInPast = $this->createFocus('Publish down in past', true);
        $publishDownInPast->setFilters(self::FILTERS);
        $publishDownInPast->setPublishDown(new \DateTime('-1 day'));

        $this->em->flush();

        // Pin how the array column type serializes an empty filters collection
        $rawEmpty = $this->connection->fetchOne('SELECT filters FROM '.MAUTIC_TABLE_PREFIX.'focus WHERE id = '.$emptyFilters->getId());
        Assert::assertSame('a:0:{}', $rawEmpty);

        $repository = $this->getRepository();
        $rows       = $repository->getPublishedWithFilters();

        Assert::assertCount(1, $rows);
        Assert::assertSame((string) $published->getId(), (string) $rows[0]['id']);
        Assert::assertSame(self::FILTERS, unserialize($rows[0]['filters']));
        Assert::assertTrue($repository->hasPublishedWithFilters());
    }

    public function testHasPublishedWithFiltersIsFalseWithoutFilterBasedItems(): void
    {
        $focus = $this->createFocus('No filters', true);
        $this->em->flush();

        Assert::assertFalse($this->getRepository()->hasPublishedWithFilters());
        Assert::assertSame([], $this->getRepository()->getPublishedWithFilters());

        $focus->setFilters(self::FILTERS);
        // Mautic entities use deferred-explicit change tracking
        $this->em->persist($focus);
        $this->em->flush();

        Assert::assertTrue($this->getRepository()->hasPublishedWithFilters());
    }

    private function createFocus(string $name, bool $isPublished): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('notice');
        $focus->setStyle('modal');
        $focus->setIsPublished($isPublished);
        $this->em->persist($focus);

        return $focus;
    }

    private function getRepository(): FocusRepository
    {
        return $this->em->getRepository(Focus::class);
    }
}
