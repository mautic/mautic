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

        $router     = self::getContainer()->get(RouterInterface::class);
        $displayUrl = $router->generate('mautic_focus_generate_display', ['id' => $focus->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        $legacyUrl  = $router->generate('mautic_focus_generate', ['id' => $focus->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        $displaySnippet  = (string) $crawler->filter('#focus-display-snippet [data-copy]')->attr('data-copy');
        $trackingSnippet = (string) $crawler->filter('#focus-tracking-snippet [data-copy]')->attr('data-copy');
        $fullSnippet     = (string) $crawler->filter('#focus-full-snippet [data-copy]')->attr('data-copy');
        $legacySnippet   = (string) $crawler->filter('#focus-legacy-snippet [data-copy]')->attr('data-copy');

        $this->assertStringContainsString($displayUrl, $displaySnippet);
        $this->assertStringContainsString('window.MauticFocusTrackingQueue['.$focus->getId().'] = true;', $trackingSnippet);
        $this->assertStringContainsString('window.MauticFocusItems['.$focus->getId().'].loadTracking();', $trackingSnippet);
        $this->assertStringContainsString($displayUrl, $fullSnippet);
        $this->assertMatchesRegularExpression('/MauticFocusTrackingQueue\['.$focus->getId().'\].*'.preg_quote($displayUrl, '/').'/s', $fullSnippet);
        $this->assertStringContainsString($legacyUrl, $legacySnippet);
        $this->assertStringContainsString('Mautic does not collect, store, or prove consent.', (string) $crawler->text());
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
        $focusModel = static::getContainer()->get(FocusModel::class);
        $focusModel->saveEntity($focus);

        $this->em->clear();

        return $focus;
    }
}
