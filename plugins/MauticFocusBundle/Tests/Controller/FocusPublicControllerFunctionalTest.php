<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Request;

final class FocusPublicControllerFunctionalTest extends MauticMysqlTestCase
{
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
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
        $this->assertStringContainsString('window.MauticFocusItems', $content);
        $this->assertStringContainsString('loadTracking', $content);
        $this->assertMatchesRegularExpression('/trackingEnabled:(?:true|!0)/', $content);
        $this->assertMatchesRegularExpression("/Focus\\.cookies\\.setItem\\(['\"]mautic_focus_{$focus->getId()}['\"]\\s*,\\s*-1\\s*,/", $content);
        $this->assertStringNotContainsString('MauticJS', $content);
        $this->assertStringNotContainsString('mtc_id', $content);
        $this->assertStringNotContainsString('mautic_device_id', $content);
        $this->assertStringNotContainsString('/mtc.js', $content);
        $this->assertStringNotContainsString('/mautic-essential.js', $content);
        $this->assertStringNotContainsString('/mautic-tracking.js', $content);
    }

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testScopedEndpointContracts(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get(FocusModel::class);
        $focus      = $this->createFocus('scoped');
        $focus->setStyle('bar');
        $focusModel->saveEntity($focus);
        $id = $focus->getId();

        foreach (["/focus/{$id}.js", "/focus/{$id}/display.js", "/focus/{$id}/tracking.js"] as $url) {
            $this->client->request(Request::METHOD_GET, $url);
            $response = $this->client->getResponse();

            $this->assertResponseIsSuccessful();
            $this->assertResponseHeaderSame('Content-Type', 'application/javascript');
            $this->assertTrue($response->headers->hasCacheControlDirective('private'));
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        }

        $this->client->request(Request::METHOD_GET, "/focus/{$id}/display.js");
        $displayContent = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString("MauticFocus{$id}", $displayContent);
        $this->assertStringContainsString("mautic_focus_{$id}", $displayContent);
        $this->assertStringContainsString("mautic_focus_{$id}_closed", $displayContent);
        $this->assertStringContainsString("mf-bar-collapser-{$id}", $displayContent);
        $this->assertStringContainsString('privacysafe.example', $displayContent);
        $this->assertStringContainsString('window.MauticFocusItems', $displayContent);
        $this->assertStringContainsString('window.MauticFocusTrackingQueue', $displayContent);
        $this->assertStringContainsString('loadTracking', $displayContent);
        $this->assertStringContainsString('DOMContentLoaded', $displayContent);
        $this->assertStringContainsString('initialized', $displayContent);
        $this->assertStringContainsString('delete window.MauticFocusItems', $displayContent);
        $this->assertMatchesRegularExpression('/trackingLoading:(?:false|!1)/', $displayContent);
        $this->assertMatchesRegularExpression('/trackingEnabled:(?:false|!1)/', $displayContent);
        $this->assertStringContainsString('tracking.js', $displayContent);
        $this->assertStringNotContainsString('viewpixel.gif', $displayContent);
        $this->assertStringNotContainsString('mauticform[focusId]', $displayContent);
        $this->assertStringNotContainsString('localStorage', $displayContent);
        $this->assertStringNotContainsString('sessionStorage', $displayContent);
        $this->assertStringNotContainsString('mtc_id', $displayContent);
        $this->assertStringNotContainsString('mautic_device_id', $displayContent);
        $this->assertStringNotContainsString('/mautic-tracking.js', $displayContent);

        $this->client->request(Request::METHOD_GET, "/focus/{$id}.js");
        $legacyContent = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('viewpixel.gif', $legacyContent);
        $this->assertStringNotContainsString('privacysafe.example', $legacyContent);

        $this->client->request(Request::METHOD_GET, "/focus/{$id}/tracking.js");
        $trackingContent = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('window.MauticFocusItems', $trackingContent);
        $this->assertStringContainsString('runtimeReady', $trackingContent);
        $this->assertStringContainsString('activateTracking', $trackingContent);
        $this->assertStringContainsString('mauticform[focusId]', $trackingContent);
        $this->assertStringContainsString('.mauticform_wrapper > form[data-mautic-form]', $trackingContent);
        $this->assertStringContainsString('viewpixel.gif', $trackingContent);
        $this->assertStringNotContainsString('mautic_focus_', $trackingContent);
        $this->assertStringNotContainsString('createIframe', $trackingContent);
        $this->assertStringNotContainsString('privacysafe.example', $trackingContent);
    }

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testInactiveFocusItemScript(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get(FocusModel::class);
        $focus      = $this->createFocus('popup');
        $focus->setIsPublished(false);
        $focusModel->saveEntity($focus);

        foreach ([
            "/focus/{$focus->getId()}.js",
            "/focus/{$focus->getId()}/display.js",
            "/focus/{$focus->getId()}/tracking.js",
            '/focus/999999999.js',
            '/focus/999999999/display.js',
            '/focus/999999999/tracking.js',
        ] as $url) {
            $this->client->request(Request::METHOD_GET, $url);
            $response = $this->client->getResponse();
            $this->assertTrue($response->isNotFound(), $url);
            $this->assertEmpty($response->getContent(), $url);
        }
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
                'headline'        => 'Privacy-safe Focus Item',
                'tagline'         => null,
                'link_text'       => null,
                'link_url'        => 'https://privacysafe.example/destination',
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
