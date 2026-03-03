<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $focusModel->saveEntity($focus);

        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/focus/edit/'.$focus->getId());
        $this->assertResponseIsSuccessful();
        $form    = $crawler->selectButton('focus_buttons_apply')->form();
        $form['focus[isPublished]']->setValue('0');
        $this->client->submit($form);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/focus/view/'.$focus->getId());
        $this->assertResponseIsSuccessful();

        $translator = self::getContainer()->get('translator');

        $this->assertStringContainsString($translator->trans('mautic.core.recent.activity'), $this->client->getResponse()->getContent());
        $this->assertCount(2, $crawler->filterXPath('//ul[contains(@class, "media-list-feed")]/li'));
    }
}
