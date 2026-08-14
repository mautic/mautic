<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class PageHitCookieTest extends MauticMysqlTestCase
{
    private HitRepository $hitRepository;

    protected function setUp(): void
    {
        $this->configParams['messenger_dsn_hit']   = 'sync://';
        $this->configParams['messenger_dsn_email'] = 'sync://';

        parent::setUp();
        $this->hitRepository = self::getContainer()->get(HitRepository::class);
    }

    public function testPageHitCookieContainsValidHitIdAndUpdatesDateLeft(): void
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setDateAdded(new \DateTime());
        $page->setTitle('Test Page for Cookie');
        $page->setAlias('test-page-cookie');
        $page->setTemplate('Blank');
        $page->setCustomHtml('<h1>Test</h1>');
        $page->setLanguage('en');
        $this->em->persist($page);
        $this->em->flush();

        $this->logoutUser();
        $this->client->request(Request::METHOD_GET, '/test-page-cookie');
        $this->assertResponseIsSuccessful();

        $cookieJar = $this->client->getCookieJar();
        $cookie    = $cookieJar->get('mautic_referer_id');
        $this->assertInstanceOf(Cookie::class, $cookie, 'Cookie mautic_referer_id should be set');

        $cookieValue = $cookie->getValue();
        $this->assertNotSame('', $cookieValue, 'Cookie value should not be empty');
        $this->assertIsNumeric($cookieValue, 'Cookie value should be numeric (the Hit ID)');

        $hits = $this->hitRepository->findBy(['page' => $page->getId()], ['dateHit' => 'ASC']);
        $this->assertCount(1, $hits);
        $firstHit = $hits[0];
        $this->assertNull($firstHit->getDateLeft(), 'First hit should not have date_left set yet');
        $this->assertEquals((int) $cookieValue, $firstHit->getId(), 'Cookie should contain the first hit ID');

        $this->client->request(Request::METHOD_GET, '/test-page-cookie');
        $this->assertResponseIsSuccessful();

        $this->em->refresh($firstHit);

        $this->assertNotNull($firstHit->getDateLeft(), 'First hit should have date_left updated after second hit');
        $this->assertInstanceOf(\DateTimeInterface::class, $firstHit->getDateLeft(), 'date_left should be a DateTime object');

        $allHits = $this->hitRepository->findBy(['page' => $page->getId()], ['dateHit' => 'ASC']);
        $this->assertCount(2, $allHits, 'Should have two hits after second page visit');
        $secondHit = $allHits[1];
        $this->assertNull($secondHit->getDateLeft(), 'Second hit should not have date_left set yet');

        $cookie      = $cookieJar->get('mautic_referer_id');
        $cookieValue = $cookie?->getValue();
        $this->assertNotNull($cookieValue, 'Cookie value should not be null after second hit');
        $this->assertEquals((int) $cookieValue, $secondHit->getId(), 'Cookie should contain the second hit ID');
    }
}
