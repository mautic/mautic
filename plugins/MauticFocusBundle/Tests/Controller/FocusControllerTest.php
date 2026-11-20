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

    public function testDetailsPageShowsTheFocusTokenAndScript(): void
    {
        $focus   = $this->createFocus();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/focus/view/'.$focus->getId());

        $this->assertResponseIsSuccessful();

        $router         = self::getContainer()->get(RouterInterface::class);
        $translator     = self::getContainer()->get(TranslatorInterface::class);
        $scriptUrl      = $router->generate('mautic_focus_generate', ['id' => $focus->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $configUrl      = $router->generate('mautic_config_edit', ['activeTabId' => 'trackingconfig'], UrlGeneratorInterface::ABSOLUTE_PATH);
        $implementation = $crawler->filter('.focus-implementation');
        $landingPages   = $crawler->filter('#focus-implementation-landing-pages');
        $external       = $crawler->filter('#focus-implementation-external-websites');
        $configLink     = $implementation->filter('a[href="'.$configUrl.'"]');

        $this->assertCount(1, $implementation);
        $this->assertCount(1, $implementation->filter('.focus-implementation__pictogram[aria-hidden="true"]'));
        $this->assertCount(1, $configLink);

        $this->assertCount(1, $landingPages);
        $this->assertStringContainsString($translator->trans('mautic.focus.install.landing_pages.description'), $landingPages->text());
        $landingPageTokens = $landingPages->filter('code');
        $this->assertCount(1, $landingPageTokens);
        $this->assertStringContainsString('code-snippet__code--contrast', (string) $landingPageTokens->attr('class'));
        $this->assertSame('{focus='.$focus->getId().'}', trim($landingPageTokens->text()));
        $this->assertSame('{focus='.$focus->getId().'}', $landingPages->filter('button[data-copy]')->attr('data-copy'));

        $this->assertCount(1, $external);
        $this->assertStringContainsString($translator->trans('mautic.focus.install.external_websites.description'), $external->text());
        $this->assertCount(1, $external->filter('.code-snippet--single'));
        $this->assertCount(0, $external->filter('script'));
        $this->assertSame('<script src="'.$scriptUrl.'" async></script>', $external->filter('[data-copy]')->attr('data-copy'));

        $responseHtml = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('enableTracking', $responseHtml);
        $this->assertStringNotContainsString('MauticFocusTrackingQueue', $responseHtml);
        $this->assertStringNotContainsString('/display.js', $responseHtml);
        $this->assertCount(0, $crawler->filter('#modal-focus-consent-installation, #modal-focus-full-installation'));
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
