<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FocusControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionIsSuccessful(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/focus');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNewActionIsSuccessful(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/focus/new');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRecentActivityFeedOnFocusDetailsPage(): void
    {
        $focus = $this->createFocus();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/focus/edit/'.$focus->getId());
        $this->assertResponseIsSuccessful();
        $form    = $crawler->selectButton('focus_buttons_apply')->form();
        $form['focus[isPublished]']->setValue('0');
        $this->client->submit($form);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/focus/view/'.$focus->getId());
        $this->assertResponseIsSuccessful();

        $translator = self::getContainer()->get(TranslatorInterface::class);

        $this->assertStringContainsString($translator->trans('mautic.core.recent.activity'), (string) $this->client->getResponse()->getContent());
        $this->assertCount(2, $crawler->filterXPath('//ul[contains(@class, "media-list-feed")]/li'));
    }

    public function testDetailsPageShowsPrivacySafeInstallationSnippets(): void
    {
        $focus   = $this->createFocus();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/focus/view/'.$focus->getId());

        $this->assertResponseIsSuccessful();

        $router         = self::getContainer()->get(RouterInterface::class);
        $translator     = self::getContainer()->get(TranslatorInterface::class);
        $displayUrl     = $router->generate('mautic_focus_generate_display', ['id' => $focus->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $legacyUrl      = $router->generate('mautic_focus_generate', ['id' => $focus->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        $configUrl      = $router->generate('mautic_config_action', ['objectAction' => 'edit', 'tab' => 'trackingconfig'], UrlGeneratorInterface::ABSOLUTE_PATH);
        $implementation = $crawler->filter('.focus-implementation');
        $landingPages   = $crawler->filter('#focus-implementation-landing-pages');
        $external       = $crawler->filter('#focus-implementation-external-websites');
        $consentButton  = $external->filter('[data-target="#modal-focus-consent-installation"]');
        $fullButton     = $external->filter('[data-target="#modal-focus-full-installation"]');
        $configLink     = $crawler->filter('#focus-shared-consent-note a');
        $responseHtml   = (string) $this->client->getResponse()->getContent();

        $displaySnippet  = (string) $crawler->filter('#focus-display-snippet [data-copy]')->attr('data-copy');
        $trackingSnippet = (string) $crawler->filter('#focus-tracking-snippet [data-copy]')->attr('data-copy');
        $fullSnippet     = (string) $crawler->filter('#focus-full-snippet [data-copy]')->attr('data-copy');

        $this->assertCount(1, $implementation);
        $this->assertCount(0, $implementation->filter('[data-toggle="collapse"], .collapse'));
        $this->assertCount(1, $implementation->filter('.focus-implementation__pictogram[aria-hidden="true"]'));
        $this->assertCount(1, $landingPages);
        $this->assertStringContainsString($translator->trans('mautic.focus.install.landing_pages.description'), $landingPages->text());
        $landingPageTokens = $landingPages->filter('code');
        $this->assertCount(2, $landingPageTokens);
        $this->assertStringContainsString('code-snippet__code--contrast', (string) $landingPageTokens->eq(0)->attr('class'));
        $this->assertStringContainsString('code-snippet__code--contrast', (string) $landingPageTokens->eq(1)->attr('class'));
        $this->assertSame('{focus='.$focus->getId().'|display}', trim($landingPageTokens->eq(0)->text()));
        $this->assertSame('{focus='.$focus->getId().'|tracking}', trim($landingPageTokens->eq(1)->text()));
        $tokenHelpers = $landingPages->filter('.type-helper-text-01');
        $this->assertCount(2, $tokenHelpers);
        $this->assertSame($translator->trans('mautic.focus.token.display.helper'), trim($tokenHelpers->eq(0)->text()));
        $this->assertSame($translator->trans('mautic.focus.token.tracking.helper'), trim($tokenHelpers->eq(1)->text()));
        $landingPageCopyButtons = $landingPages->filter('button[data-copy]');
        $this->assertCount(2, $landingPageCopyButtons);
        $this->assertSame('{focus='.$focus->getId().'|display}', $landingPageCopyButtons->eq(0)->attr('data-copy'));
        $this->assertSame('{focus='.$focus->getId().'|tracking}', $landingPageCopyButtons->eq(1)->attr('data-copy'));
        $this->assertCount(1, $external);
        $this->assertCount(1, $consentButton);
        $this->assertSame($translator->trans('mautic.focus.install.consent.tab'), trim($consentButton->text()));
        $this->assertStringContainsString('mb-4', (string) $consentButton->attr('class'));
        $this->assertCount(1, $fullButton);
        $this->assertSame($translator->trans('mautic.focus.install.full.tab'), trim($fullButton->text()));
        $this->assertCount(1, $crawler->filter('#modal-focus-consent-installation'));
        $this->assertCount(1, $crawler->filter('#modal-focus-full-installation'));
        $this->assertCount(0, $crawler->filter('#focus-installation-tabs'));
        $this->assertCount(1, $crawler->filter('#focus-display-snippet .code-snippet--multi'));
        $this->assertCount(1, $crawler->filter('#focus-tracking-snippet .code-snippet--multi'));
        $this->assertCount(1, $crawler->filter('#focus-full-snippet .code-snippet--multi'));
        $this->assertCount(0, $crawler->filter('#focus-display-snippet script, #focus-tracking-snippet script, #focus-full-snippet script'));
        $this->assertCount(1, $configLink);
        $this->assertSame($configUrl, $configLink->attr('href'));
        $this->assertSame($translator->trans('mautic.config.tab.trackingconfig'), trim($configLink->text()));
        $this->assertSame('<script async src="'.$displayUrl.'"></script>', $displaySnippet);
        $this->assertStringNotContainsString('MauticFocusTrackingQueue', $displaySnippet);
        $this->assertStringNotContainsString('enableTracking', $displaySnippet);
        $this->assertStringNotContainsString('loadTracking', $displaySnippet);
        $this->assertStringContainsString('window.MauticFocus.enableTracking('.$focus->getId().');', $trackingSnippet);
        $this->assertStringContainsString('window.MauticFocusTrackingQueue['.$focus->getId().'] = true;', $trackingSnippet);
        $this->assertStringContainsString($displayUrl, $fullSnippet);
        $this->assertMatchesRegularExpression('/MauticFocusTrackingQueue\['.$focus->getId().'\].*'.preg_quote($displayUrl, '/').'/s', $fullSnippet);
        $this->assertSame(1, substr_count($fullSnippet, '<script>'));
        $this->assertStringContainsString('window.MauticFocus.enableTracking('.$focus->getId().')', $crawler->text());
        $this->assertStringNotContainsString($legacyUrl, $responseHtml);
        $this->assertCount(0, $crawler->filter('#focus-legacy-snippet'));
        $this->assertStringContainsString('Mautic does not record or verify consent.', $crawler->text());
    }

    private function createFocus(): Focus
    {
        $focus = new Focus();
        $focus->setName('Test Focus');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'bar' => [
                'allow_hide' => 1,
                'push_page'  => 1,
                'sticky'     => 1,
                'size'       => 'large',
                'placement'  => 'top',
            ],
            'modal' => [
                'placement' => 'top',
            ],
            'notification' => [
                'placement' => 'top_left',
            ],
            'page'            => [],
            'animate'         => 0,
            'link_activation' => 1,
            'colors'          => [
                'primary'     => '4e5d9d',
                'text'        => '000000',
                'button'      => 'fdb933',
                'button_text' => 'ffffff',
            ],
            'content' => [
                'headline'        => null,
                'tagline'         => null,
                'link_text'       => null,
                'link_url'        => null,
                'link_new_window' => 1,
                'font'            => 'Arial, Helvetica, sans-serif',
                'css'             => null,
            ],
            'when'                  => 'immediately',
            'timeout'               => null,
            'frequency'             => 'everypage',
            'stop_after_conversion' => 1,
        ]);

        /** @var FocusModel $focusModel */
        $focusModel = self::getContainer()->get(FocusModel::class);
        $focusModel->saveEntity($focus);

        $this->em->clear();

        return $focus;
    }
}
