<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;

final class PageRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testResetVariants(): void
    {
        /** @var PageModel $pageModel */
        $pageModel = self::getContainer()->get('mautic.page.model.page');

        // Create parent page.
        $parentPage = new Page();
        $parentPage->setTitle('Page One');
        $parentPage->setTemplate('blank');
        $parentPage->setCustomHtml('This is Page One');
        $parentPage->setHits(10);

        $pageModel->saveEntity($parentPage);

        // Create variant page.
        $variantPage = new Page();
        $variantPage->setTitle('Page One variant');
        $variantPage->setTemplate('blank');
        $variantPage->setCustomHtml('This is Page One variant');
        $variantPage->setVariantSettings(['weight' => 10]);
        $variantPage->setVariantParent($parentPage);

        $pageModel->saveEntity($variantPage);

        // Variant hits will be zero.
        $this->assertSame(0, $variantPage->getVariantHits());

        // Add some variant hits and save entity.
        $variantPage->setVariantHits(5);
        $pageModel->saveEntity($variantPage);
        $this->assertNotEmpty($variantPage->getVariantHits());

        $this->assertSame(5, $variantPage->getVariantHits());

        // Change the variant setting this will cause the variant hits to reset to zero.
        $variantPage->setVariantSettings(['weight' => 30]);
        $pageModel->saveEntity($variantPage);

        $this->assertSame(0, $variantPage->getVariantHits());
    }
}
