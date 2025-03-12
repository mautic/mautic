<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class PageModelTest extends MauticMysqlTestCase
{
    private HitRepository $pageHitRepository;

    private const DO_NOT_TRACK_IP = '218.30.65.10';

    private const BOT_BLOCKED_IP = '218.30.65.11';

    private const IP_NOT_IN_ANY_BLOCK_LIST = '218.30.65.12';

    private const IP_NOT_IN_ANY_BLOCK_LIST2 = '218.30.65.111';

    private const BOT_BLOCKED_USER_AGENTS = [
        'AHC/2.1',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.6) Gecko/2009011913 Firefox/3.0.6',
        'Mozilla/5.0 (compatible; Codewisebot/2.0; +http://www.nosite.com/somebot.htm)',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B411 Safari/600.1.4 (compatible; YandexMobileBot/3.0; +http://yandex.com/bots)',
        'serpstatbot/2.0 beta (advanced backlink tracking bot; http://serpstatbot.com/; abuse@serpstatbot.com)',
        'Mozilla/5.0 (Linux; Android 7.0;) AppleWebKit/537.36 (KHTML, like Gecko) Mobile Safari/537.36 (compatible; AspiegelBot)',
        'serpstatbot/2.1 (advanced backlink tracking bot; https://serpstatbot.com/; abuse@serpstatbot.com)',
    ];

    protected function setUp(): void
    {
        $this->configParams['do_not_track_ips']                = [self::DO_NOT_TRACK_IP];
        $this->configParams['bot_helper_blocked_ip_addresses'] = [self::BOT_BLOCKED_IP];
        $this->configParams['bot_helper_blocked_user_agents']  = self::BOT_BLOCKED_USER_AGENTS;
        $this->configParams['site_url']                        = 'https://mautic-cloud.local';
        parent::setUp();
        $this->pageHitRepository = $this->em->getRepository(Hit::class);
        $this->logoutUser();
    }

    public function testItRegistersPageHitsWithFieldValues(): void
    {
        $requestParameters = [
            'page_title'       => $this->generateRandomString(50),
            'page_language'    => $this->generateRandomString(50),
            'page_url'         => 'https://some.page.url/test/'.$this->generateRandomString(50),
            'counter'          => 0,
            'timezone_offset'  => -120,
            'resolution'       => '2560x1440',
            'platform'         => 'MacOs',
            'do_not_track'     => 'false',
            'mautic_device_id' => 'some_device_id',
        ];
        $this->client->request(Request::METHOD_POST, '/mtc/event', $requestParameters);
        /** @var Hit $pageHit */
        $pageHit = $this->pageHitRepository->findOneBy([]);
        Assert::assertInstanceOf(Hit::class, $pageHit);
        Assert::assertStringStartsWith($pageHit->getUrlTitle(), $requestParameters['page_title']);
        Assert::assertStringStartsWith($pageHit->getPageLanguage(), $requestParameters['page_language']);
        Assert::assertStringStartsWith($pageHit->getUrl(), $requestParameters['page_url']);
    }

    public function generateRandomString(int $length): string
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    /**
     * @dataProvider pageHitBotScenariosProvider
     */
    public function testItNotRegistersPageHitsFromBot(string $trackingHash, string $sentBefore, string $userAgent, string $ipAddress, bool $isHit): void
    {
        $lead = new Lead();
        $lead->setFirstname('Test Page Hit');
        $this->em->persist($lead);

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $this->em->persist($email);

        $this->em->flush();

        $emailId      = $email->getId();
        $clickThrough = [
            'source'  => ['email', $emailId],
            'email'   => $emailId,
            'stat'    => $trackingHash,
            'lead'    => $lead->getId(),
            'channel' => [
                'email' => $emailId,
            ],
            'mtc_redirect_destination' => 'https://some.page.url/test/redirect',
        ];

        $requestParameters = [
            'page_title'       => $this->generateRandomString(50),
            'page_language'    => $this->generateRandomString(50),
            'page_url'         => 'https://some.page.url/test/'.$this->generateRandomString(50),
            'counter'          => 0,
            'timezone_offset'  => -120,
            'resolution'       => '2560x1440',
            'platform'         => 'MacOs',
            'do_not_track'     => 'false',
            'mautic_device_id' => 'some_device_id',
            'ct'               => base64_encode(serialize($clickThrough)),
        ];

        // Create Email Stat
        $emailStat = new Stat();
        $emailStat->setEmailAddress('lukas.sykora@acquia.com');
        $emailStat->setTrackingHash($trackingHash);
        $emailSendTime = new \DateTime();
        $emailStat->setDateSent($emailSendTime->modify($sentBefore));
        $this->em->persist($emailStat);
        $this->em->flush();

        // Send Request
        $server = [
            'HTTP_USER_AGENT' => $userAgent,
            'REMOTE_ADDR'     => $ipAddress,
        ];
        $this->client->request(Request::METHOD_POST, '/mtc/event', $requestParameters, [], $server);
        /** @var Hit $pageHit */
        $pageHit = $this->pageHitRepository->findOneBy([]);

        if ($isHit) {
            Assert::assertInstanceOf(Hit::class, $pageHit);
            Assert::assertStringStartsWith($pageHit->getUrlTitle(), $requestParameters['page_title']);
            Assert::assertStringStartsWith($pageHit->getPageLanguage(), $requestParameters['page_language']);
            Assert::assertStringStartsWith($pageHit->getUrl(), $requestParameters['page_url']);
        } else {
            Assert::assertNull($pageHit);
        }
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function pageHitBotScenariosProvider(): iterable
    {
        // $trackingHash, $sentBefore, $userAgent, $ipAddress, $isHit
        yield 'All good' => ['test_hash_bot_ratio_1', '-80 second', 'Mozilla/5.0', self::IP_NOT_IN_ANY_BLOCK_LIST, true];
        yield 'Time and User' => ['test_hash_bot_ratio_2', '+80 second', 'AHC/2.1', self::IP_NOT_IN_ANY_BLOCK_LIST, false];
        yield 'Time and IP' => ['test_hash_bot_ratio_3', '+80 second', 'Mozilla/5.0', self::BOT_BLOCKED_IP, false];
        yield 'Permanently blocked IP' => ['test_hash_bot_ratio_4', '-80 second', 'Mozilla/5.0', self::DO_NOT_TRACK_IP, false];
        yield 'Bot Blocked IP address only' => ['test_hash_bot_ratio_5', '-80 second', 'Mozilla/5.0', self::BOT_BLOCKED_IP, true];
        yield 'Bot Blocked User Agent only' => ['test_hash_bot_ratio_6', '-80 second', 'AHC/2.1', self::IP_NOT_IN_ANY_BLOCK_LIST, true];
        yield 'Time Only' => ['test_hash_bot_ratio_7', '+80 second', 'Mozilla/5.0', self::IP_NOT_IN_ANY_BLOCK_LIST, true];
        yield 'Time and Bot User Agent and Bot IP' => ['test_hash_bot_ratio_8', '+80 second', 'AHC/2.1', self::BOT_BLOCKED_IP, false];
        yield 'Bot User Agent and Bot IP' => ['test_hash_bot_ratio_9', '-80 second', 'AHC/2.1', self::BOT_BLOCKED_IP, false];
        yield 'Permanently blocked User Agent' => ['test_hash_bot_ratio_10', '-80 second', 'MSNBOT', self::IP_NOT_IN_ANY_BLOCK_LIST2, false];
    }

    /**
     * @dataProvider pageHitBotScenariosProvider
     */
    public function testRedirect(string $trackingHash, string $sentBefore, string $userAgent, string $ipAddress, bool $isHit): void
    {
        $lead = new Lead();
        $lead->setFirstname('Test Page Hit');
        $this->em->persist($lead);

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $this->em->persist($email);

        $this->em->flush();

        $page = new Page();
        $page->setTitle('Page A');
        $page->setAlias('page_a');
        $page->setCustomHtml('Page A');
        $page->setRedirectUrl('http://mautic-cloud.local/page_a');
        $this->em->persist($page);

        $this->em->flush();

        $emailId      = $email->getId();
        $clickThrough = [
            'source'  => ['email', $emailId],
            'email'   => $emailId,
            'stat'    => $trackingHash,
            'lead'    => $lead->getId(),
            'channel' => [
                'email' => $emailId,
            ],
            'mtc_redirect_destination' => 'http://mautic-cloud.local/page_a',
        ];

        // Create Email Stat
        $emailStat = new Stat();
        $emailStat->setEmailAddress('lukas.sykora@acquia.com');
        $emailStat->setTrackingHash($trackingHash);
        $emailSendTime = new \DateTime();
        $emailStat->setDateSent($emailSendTime->modify($sentBefore));
        $this->em->persist($emailStat);
        $this->em->flush();

        $redirectId = 'abc';
        $redirect   = new Redirect();
        $redirect->setRedirectId($redirectId);
        $redirect->setUrl('http://mautic-cloud.local/page_a');
        $this->em->persist($redirect);
        $this->em->flush();

        $redirectModel = $this->getContainer()->get('mautic.page.model.redirect');
        $redirectURL   = $redirectModel->generateRedirectUrl($redirect, $clickThrough);
        // Send Request
        $server = [
            'HTTP_USER_AGENT' => $userAgent,
            'REMOTE_ADDR'     => $ipAddress,
        ];

        $this->client->request(Request::METHOD_GET, $redirectURL, [], [], $server);
        /** @var Hit $pageHit */
        $pageHit = $this->pageHitRepository->findOneBy([]);

        if ($isHit) {
            Assert::assertInstanceOf(Hit::class, $pageHit);
            Assert::assertStringStartsWith($pageHit->getUrl(), $page->getRedirectUrl());
        } else {
            Assert::assertNull($pageHit);
        }
    }
}
