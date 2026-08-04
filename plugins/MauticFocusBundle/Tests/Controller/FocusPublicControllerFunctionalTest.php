<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\HttpFoundation\Request;

final class FocusPublicControllerFunctionalTest extends MauticMysqlTestCase
{
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testGenerateFocusItemScript(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get(FocusModel::class);
        $focus      = $this->createFocus('popup');
        $focus->setStyle('bar');
        $focusModel->saveEntity($focus);

        $this->client->request(Request::METHOD_GET, "/focus/{$focus->getId()}.js");
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $content = (string) $response->getContent();

        $this->assertStringContainsString("MauticFocus{$focus->getId()}", $content);
        $this->assertStringContainsString("mautic_focus_{$focus->getId()}", $content);
        $this->assertStringContainsString("mautic_focus_{$focus->getId()}_closed", $content);
        $this->assertStringContainsString("mf-bar-collapser-{$focus->getId()}", $content);
        $this->assertMatchesRegularExpression("/Focus\\.cookies\\.setItem\\(['\"]mautic_focus_{$focus->getId()}['\"]\\s*,\\s*-1\\s*,/", $content);
        $this->assertStringNotContainsString('MauticJS', $content);
        $this->assertStringNotContainsString('mtc_id', $content);
        $this->assertStringNotContainsString('mautic_device_id', $content);
        $this->assertStringNotContainsString('/mtc.js', $content);
        $this->assertStringNotContainsString('/mautic-essential.js', $content);
        $this->assertStringNotContainsString('/mautic-tracking.js', $content);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testInactiveFocusItemScript(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get(FocusModel::class);
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
