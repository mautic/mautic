<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Request;

class FocusPublicControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testGenerateFocusItemScript(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $focus      = $this->createFocus('popup');
        $focusModel->saveEntity($focus);

        $this->client->request(Request::METHOD_GET, "/focus/{$focus->getId()}.js");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertNotEmpty($response->getContent());
    }

    public function testInactiveFocusItemScript(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $focus      = $this->createFocus('popup');
        $focus->setIsPublished(false);
        $focusModel->saveEntity($focus);

        $this->client->request(Request::METHOD_GET, "/focus/{$focus->getId()}.js");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isNotFound());
        $this->assertEmpty($response->getContent());
    }

    private function createFocus(string $name): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
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

        return $focus;
    }
}
