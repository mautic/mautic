<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Model;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;

/**
 * The asset category lookup must return asset and global categories, and nothing from
 * another bundle.
 *
 * getCategoryList() takes the bundle first, and AssetModel used to omit it, so every
 * argument landed one place to the left: the search text became the bundle and the limit
 * became the search. The lookup returned an empty list for any input.
 */
final class AssetCategoryLookupFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testLookupReturnsAssetAndGlobalCategoriesOnly(): void
    {
        $this->createCategory('Invoices', 'lookup-asset', 'asset');
        $this->createCategory('Invoicing shared', 'lookup-global', 'global');
        $this->createCategory('Invoice pages', 'lookup-page', 'page');
        $this->em->flush();

        $titles = $this->lookup('Invoic');

        $this->assertContains('Invoices', $titles, 'An asset category matching the search must be returned.');
        $this->assertContains('Invoicing shared', $titles, 'Global categories are offered alongside the bundle\'s own.');
        $this->assertNotContains('Invoice pages', $titles, 'A category belonging to another bundle must not be offered.');
    }

    public function testLookupNarrowsByTheSearchTerm(): void
    {
        $this->createCategory('Contracts', 'lookup-contracts', 'asset');
        $this->createCategory('Invoices', 'lookup-invoices', 'asset');
        $this->em->flush();

        $titles = $this->lookup('Contr');

        $this->assertContains('Contracts', $titles);
        $this->assertNotContains('Invoices', $titles, 'The search term must still filter, rather than being swallowed as another argument.');
    }

    /**
     * @return list<string>
     */
    private function lookup(string $search): array
    {
        $model = self::getContainer()->get(AssetModel::class);
        $this->assertInstanceOf(AssetModel::class, $model);

        return array_values(array_map(
            static fn (array $row): string => (string) ($row['title'] ?? ''),
            $model->getLookupResults('category', $search, 10)
        ));
    }
}
