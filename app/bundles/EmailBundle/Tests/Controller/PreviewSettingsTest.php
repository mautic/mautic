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

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\HttpFoundation\Request;

class PreviewSettingsTest extends MauticMysqlTestCase
{
    public function testPreviewSettingsAllEnabled(): void
    {
        $emailMain = new Email();
        $emailMain->setIsPublished(true);
        $emailMain->setDateAdded(new \DateTime());
        $emailMain->setName('Preview settings test');
        $emailMain->setSubject('email-main');
        $emailMain->setTemplate('Blank');
        $emailMain->setCustomHtml('Test Html');
        $emailMain->setLanguage('en');

        $this->em->persist($emailMain);
        $this->em->flush();

        $mainPageId = $emailMain->getId();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $this->assertStringContainsString(
            'Preview settings test',
            $crawler->filterXPath('//*[@id="app-content"]/div/div[2]/div[2]/div[1]/table/tbody/tr/td[2]/div/a/text()')->text()
        );

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$mainPageId}");

        // Translation choice is not visible
        $this->assertCount(
            0,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]')
        );

        // Variant choice is not visible
        $this->assertCount(
            0,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]')
        );

        // Contact lookup is not visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_contact"]')
        );

        $pageTranslated = new Email();
        $pageTranslated->setIsPublished(true);
        $pageTranslated->setDateAdded(new \DateTime());
        $pageTranslated->setName('Preview settings test - NL translation');
        $pageTranslated->setSubject('page-trans-nl');
        $pageTranslated->setTemplate('Blank');
        $pageTranslated->setCustomHtml('Test Html');
        $pageTranslated->setLanguage('nl_CW');

        // Add translation relationship to main page
        $emailMain->addTranslationChild($pageTranslated);
        $pageTranslated->setTranslationParent($emailMain);

        $pageVariant = new Email();
        $pageVariant->setIsPublished(true);
        $pageVariant->setDateAdded(new \DateTime());
        $pageVariant->setName('Preview settings test - B variant');
        $pageVariant->setSubject('page-variant-b');
        $pageVariant->setTemplate('Blank');
        $pageVariant->setCustomHtml('Test Html');
        $pageVariant->setLanguage('en');

        // Add variant relationship to main page
        $emailMain->addVariantChild($pageVariant);

        $this->em->persist($emailMain);
        $this->em->persist($pageTranslated);
        $this->em->persist($pageVariant);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$mainPageId}");

        // Translation choice is visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]')
        );

        // Variant choice is visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]')
        );

        // Contact lookup is visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_contact"]')
        );
    }
}
