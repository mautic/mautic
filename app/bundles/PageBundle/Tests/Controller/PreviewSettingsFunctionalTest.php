<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\Request;

class PreviewSettingsFunctionalTest extends MauticMysqlTestCase
{
    public function testPreviewSettingsAllEnabled(): void
    {
        $pageMain = new Page();
        $pageMain->setIsPublished(true);
        $pageMain->setDateAdded(new \DateTime());
        $pageMain->setTitle('Preview settings test - main page');
        $pageMain->setAlias('page-main');
        $pageMain->setTemplate('Blank');
        $pageMain->setCustomHtml('Test Html');
        $pageMain->setLanguage('en');

        $this->em->persist($pageMain);
        $this->em->flush();

        $mainPageId = $pageMain->getId();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/pages');
        self::assertStringContainsString($pageMain->getTitle(), $crawler->text());

        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/view/{$mainPageId}");

        // Translation choice is not visible
        self::assertCount(
            0,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]')
        );

        // Variant choice is not visible
        self::assertCount(
            0,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]')
        );

        // Contact lookup is not visible
        self::assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_contact"]')
        );

        $pageTranslated = new Page();
        $pageTranslated->setIsPublished(true);
        $pageTranslated->setDateAdded(new \DateTime());
        $pageTranslated->setTitle('Preview settings test - NL translation');
        $pageTranslated->setAlias('page-trans-nl');
        $pageTranslated->setTemplate('Blank');
        $pageTranslated->setCustomHtml('Test Html');
        $pageTranslated->setLanguage('nl_CW');

        // Add translation relationship to main page
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

        // Add variant relationship to main page
        $pageMain->addVariantChild($pageVariant);

        $this->em->persist($pageMain);
        $this->em->persist($pageTranslated);
        $this->em->persist($pageVariant);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/pages/view/{$mainPageId}");

        // Translation choice is visible
        self::assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]')
        );

        self::assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_translation"]/option[@value="'.$pageTranslated->getId().'"]')
        );

        // Variant choice is visible
        self::assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]')
        );

        self::assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_variant"]/option[@value="'.$pageVariant->getId().'"]')
        );

        // Contact lookup is visible
        self::assertCount(
            1,
            $crawler->filterXPath('//*[@id="content_preview_settings_contact"]')
        );
    }
}
