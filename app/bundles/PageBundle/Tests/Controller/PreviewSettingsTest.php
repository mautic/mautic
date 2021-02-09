<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class PreviewSettingsTest extends MauticMysqlTestCase
{
    public function testPreviewSettings(): void
    {
        $pageMain = new Page();
        $pageMain->setIsPublished(true);
        $pageMain->setDateAdded(new \DateTime());
        $pageMain->setTitle('Preview settings test - main page');
        $pageMain->setAlias('page-main');
        $pageMain->setTemplate('Blank');
        $pageMain->setCustomHtml('Test Html');
        $pageMain->setLanguage('en');

        $pageTranslated = new Page();
        $pageTranslated->setIsPublished(true);
        $pageTranslated->setDateAdded(new \DateTime());
        $pageTranslated->setTitle('Preview settings test - NL translation');
        $pageTranslated->setAlias('page-trans-nl');
        $pageTranslated->setTemplate('Blank');
        $pageTranslated->setCustomHtml('Test Html');
        $pageTranslated->setLanguage('nl_CW');

        $pageMain->addTranslationChild($pageTranslated);
        $pageTranslated->setTranslationParent($pageMain);

        $pageVariant = new Page();
        $pageVariant->setIsPublished(true);
        $pageVariant->setDateAdded(new \DateTime());
        $pageVariant->setTitle('Preview settings test - B variant');
        $pageVariant->setAlias('page-variant-b');
        $pageVariant->setTemplate('Blank');
        $pageVariant->setCustomHtml('Test Html');
        $pageVariant->setLanguage('en');

        $pageMain->addVariantChild($pageVariant);

        $this->em->persist($pageMain);
        $this->em->persist($pageTranslated);
        $this->em->persist($pageVariant);
        $this->em->flush();

        // list landing page
        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages');

        // check added landing page is listed or not
        $this->assertStringContainsString(
            'Preview settings test - main page (page-main)',
            $crawler->filterXPath('//*[@id="pageTable"]/tbody/tr[1]/td[2]/a')->text()
        );
    }
}
