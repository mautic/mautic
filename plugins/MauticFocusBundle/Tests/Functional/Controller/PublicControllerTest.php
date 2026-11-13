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

final class PublicControllerTest extends MauticMysqlTestCase
{
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testGenerateActionShowsContactTokensWithoutTheContact(): void
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
        $contactTracker = self::getContainer()->get(ContactTracker::class);
        $contactTracker->setSystemContact($contact);

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $displayContent = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('viewpixel.gif', $displayContent);
        $this->assertStringNotContainsString('DisplayFocusContact', $displayContent);
        $this->assertStringContainsString('anonymous.example', $displayContent);
        $this->assertStringContainsString('visitor', $displayContent);
        $this->assertStringContainsString('mautic:tracking-enabled', $displayContent);
        $this->assertStringNotContainsString('contactfield', $displayContent);
        $this->assertCount(0, $this->em->getRepository(Redirect::class)->findAll());
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
    public function testResponsesAreNotSharedBetweenContacts(): void
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
        $contactTracker = self::getContainer()->get(ContactTracker::class);
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

        // The script that shows the item never includes the contact
        $contactTracker->setSystemContact($firstContact);
        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $displayResponse = $this->client->getResponse();
        $displayContent  = (string) $displayResponse->getContent();
        $this->assertTrue($displayResponse->headers->hasCacheControlDirective('private'));
        $this->assertTrue($displayResponse->headers->hasCacheControlDirective('no-store'));
        $this->assertStringNotContainsString('AlphaFocusContact', $displayContent);
        $this->assertStringNotContainsString('BetaFocusContact', $displayContent);
    }
}
