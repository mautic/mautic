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
use Symfony\Bundle\FrameworkBundle\Client;
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

        $emailTranslated = new Email();
        $emailTranslated->setIsPublished(true);
        $emailTranslated->setDateAdded(new \DateTime());
        $emailTranslated->setName('Preview settings test - NL translation');
        $emailTranslated->setSubject('page-trans-nl');
        $emailTranslated->setTemplate('Blank');
        $emailTranslated->setCustomHtml('Test Html');
        $emailTranslated->setLanguage('nl_CW');

        // Add translation relationship to main page
        $emailMain->addTranslationChild($emailTranslated);
        $emailTranslated->setTranslationParent($emailMain);

        $emailVariant = new Email();
        $emailVariant->setIsPublished(true);
        $emailVariant->setDateAdded(new \DateTime());
        $emailVariant->setName('Preview settings test - B variant');
        $emailVariant->setSubject('page-variant-b');
        $emailVariant->setTemplate('Blank');
        $emailVariant->setCustomHtml('Test Html');
        $emailVariant->setLanguage('en');

        // Add variant relationship to main page
        $emailMain->addVariantChild($emailVariant);

        $this->em->persist($emailMain);
        $this->em->persist($emailTranslated);
        $this->em->persist($emailVariant);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$mainPageId}");

        // Translation choice is visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]')
        );

        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]/option[@value="'.$emailTranslated->getId().'"]')
        );

        // Variant choice is visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]')
        );

        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]/option[@value="'.$emailVariant->getId().'"]')
        );

        // Contact lookup is visible
        $this->assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_contact"]')
        );

        $client  = $this->createSalesUserCrawler();
        $crawler = $client->request(Request::METHOD_GET, "/s/emails/view/{$mainPageId}");

        // Contact lookup is not visible to user without access
        $this->assertCount(
            0,
            $crawler->filterXPath('//*[@id="content_preview_settings_contact"]')
        );
    }

    private function createSalesUserCrawler(): Client
    {
        return self::createClient(
            $this->clientOptions,
            [
                'PHP_AUTH_USER' => 'sales',
                'PHP_AUTH_PW'   => 'mautic',
            ]
        );
    }
}
