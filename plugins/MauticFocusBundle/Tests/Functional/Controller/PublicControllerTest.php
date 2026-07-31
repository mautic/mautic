<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Redirect;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

final class PublicControllerTest extends MauticMysqlTestCase
{
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testGenerateActionWithContactTokenInLinkUrl(): void
    {
        $linkUrl = 'https://anonymous.example/{contactfield=firstname|visitor}/tour';
        $focus   = new Focus();
        $focus->setName('Test');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'content' => [
                'headline'        => '',
                'link_text'       => 'Link text',
                'link_url'        => $linkUrl,
                'font'            => 'Arial, Helvetica, sans-serif',
                'link_new_window' => 1,
            ],
            'when'  => 'immediately',
            'modal' => [
                'placement' => 'top',
            ],
            'frequency' => 'everypage',
            'colors'    => [
                'primary'     => '#4e5d9d',
                'text'        => '#000000',
                'button'      => '#fdb933',
                'button_text' => '#ffffff',
            ],
        ]);
        $this->em->persist($focus);

        $contact = new Lead();
        $contact->setFirstname('DisplayFocusContact');
        $this->em->persist($contact);
        $this->em->flush();
        $this->em->clear();

        /** @var ContactTracker $contactTracker */
        $contactTracker = static::getContainer()->get(ContactTracker::class);
        $contactTracker->setSystemContact($contact);

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s/display.js', $focus->getId()));
        $displayContent = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('viewpixel.gif', $displayContent);
        $this->assertStringNotContainsString('DisplayFocusContact', $displayContent);
        $this->assertStringContainsString('anonymous.example', $displayContent);
        $this->assertStringContainsString('visitor', $displayContent);
        $this->assertStringContainsString('MauticFocusUseMauticTrackingConsent', $displayContent);
        $this->assertStringContainsString('mautic:tracking-enabled', $displayContent);
        $this->assertStringNotContainsString('contactfield', $displayContent);
        $this->assertCount(0, $this->em->getRepository(Redirect::class)->findAll());

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $content = $this->client->getResponse()->getContent();

        $redirects = $this->em->getRepository(Redirect::class)->findAll();
        $this->assertCount(1, $redirects);

        /** @var Redirect $redirect */
        $redirect = reset($redirects);
        $this->assertSame($linkUrl, $redirect->getUrl());

        $url  = $this->router->generate('mautic_url_redirect', ['redirectId' => $redirect->getRedirectId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $twig = $this->getContainer()->get(Environment::class);
        if (!$twig->hasExtension(EscaperExtension::class)) {
            $twig->addExtension(new EscaperExtension());
        }
        $url = $twig->getRuntime(EscaperRuntime::class)->escape($url, 'js');
        $this->assertStringContainsString($url, (string) $content);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testTrackingActionCreatesTrackedRedirect(): void
    {
        $focus = new Focus();
        $focus->setName('Tracking redirect');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'content' => [
                'link_url' => 'https://example.com/tracking',
            ],
        ]);
        $this->em->persist($focus);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s/tracking.js', $focus->getId()));

        $this->assertStringContainsString('activateTracking', (string) $this->client->getResponse()->getContent());
        $this->assertCount(1, $this->em->getRepository(Redirect::class)->findAll());
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testLegacyResponsesAreNotSharedBetweenContacts(): void
    {
        $focus = new Focus();
        $focus->setName('Contact isolation test');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'content' => [
                'headline'        => '{contactfield=firstname}',
                'link_text'       => 'Link text',
                'link_url'        => 'https://example.com/tour',
                'font'            => 'Arial, Helvetica, sans-serif',
                'link_new_window' => 1,
            ],
            'when'  => 'immediately',
            'modal' => [
                'placement' => 'top',
            ],
            'frequency' => 'everypage',
            'colors'    => [
                'primary'     => '#4e5d9d',
                'text'        => '#000000',
                'button'      => '#fdb933',
                'button_text' => '#ffffff',
            ],
        ]);
        $this->em->persist($focus);

        $firstContact = new Lead();
        $firstContact->setFirstname('AlphaFocusContact');
        $this->em->persist($firstContact);

        $secondContact = new Lead();
        $secondContact->setFirstname('BetaFocusContact');
        $this->em->persist($secondContact);

        $this->em->flush();
        $this->em->clear();

        /** @var ContactTracker $contactTracker */
        $contactTracker = static::getContainer()->get(ContactTracker::class);
        $contactTracker->setSystemContact($firstContact);

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s/tracking.js', $focus->getId()));
        $firstTrackingResponse = $this->client->getResponse();
        $firstTrackingContent  = (string) $firstTrackingResponse->getContent();
        $this->assertTrue($firstTrackingResponse->headers->hasCacheControlDirective('private'));
        $this->assertTrue($firstTrackingResponse->headers->hasCacheControlDirective('no-store'));

        $contactTracker->setSystemContact($secondContact);
        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s/tracking.js', $focus->getId()));
        $secondTrackingResponse = $this->client->getResponse();
        $secondTrackingContent  = (string) $secondTrackingResponse->getContent();
        $this->assertTrue($secondTrackingResponse->headers->hasCacheControlDirective('private'));
        $this->assertTrue($secondTrackingResponse->headers->hasCacheControlDirective('no-store'));
        $this->assertNotSame($firstTrackingContent, $secondTrackingContent);

        $contactTracker->setSystemContact($firstContact);
        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $firstResponse = $this->client->getResponse();
        $firstContent  = (string) $firstResponse->getContent();
        $this->assertTrue($firstResponse->headers->hasCacheControlDirective('private'));
        $this->assertTrue($firstResponse->headers->hasCacheControlDirective('no-store'));
        $this->assertStringContainsString('AlphaFocusContact', $firstContent);
        $this->assertStringNotContainsString('BetaFocusContact', $firstContent);

        $contactTracker->setSystemContact($secondContact);
        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $secondResponse = $this->client->getResponse();
        $secondContent  = (string) $secondResponse->getContent();
        $this->assertTrue($secondResponse->headers->hasCacheControlDirective('private'));
        $this->assertTrue($secondResponse->headers->hasCacheControlDirective('no-store'));
        $this->assertStringContainsString('BetaFocusContact', $secondContent);
        $this->assertStringNotContainsString('AlphaFocusContact', $secondContent);
        $this->assertNotSame($firstContent, $secondContent);
    }
}
