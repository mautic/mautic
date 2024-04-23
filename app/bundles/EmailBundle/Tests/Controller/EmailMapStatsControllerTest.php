<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Controller\EmailMapStatsController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailMapStatsControllerTest extends MauticMysqlTestCase
{
    private MockObject $emailModelMock;

    private EmailMapStatsController $mapController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailModelMock = $this->createMock(EmailModel::class);
        $this->mapController  = new EmailMapStatsController($this->emailModelMock);
    }

    /**
     * @throws ORMException
     */
    public function testHasAccess(): void
    {
        $corePermissionsMock = $this->createMock(CorePermissions::class);

        $role = new Role();
        $role->setName('Example admin');
        $this->em->persist($role);
        $this->em->flush();

        $user = new User();
        $user->setFirstName('Example');
        $user->setLastName('Example');
        $user->setUsername('Example');
        $user->setPassword('123456');
        $user->setEmail('example@example.com');
        $user->setRole($role);
        $this->em->persist($user);
        $this->em->flush();

        $email = new Email();
        $email->setName('Test email 1');
        $email->setCreatedBy($user);
        $this->em->persist($email);
        $this->em->flush();

        $corePermissionsMock->method('hasEntityAccess')
            ->with(
                'email:emails:viewown',
                'email:emails:viewother',
                $user->getId()
            )
            ->willReturn(false);

        $result = $this->mapController->hasAccess($corePermissionsMock, $email);

        try {
            $this->mapController->viewAction($corePermissionsMock, $email->getId(), '2023-07-20', '2023-07-27');
        } catch (AccessDeniedHttpException|\Exception $e) {
            $this->assertTrue($e instanceof AccessDeniedHttpException);
        }

        $this->assertFalse($result);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testGetMapOptions(): void
    {
        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        $result = $this->mapController->getMapOptions($email);
        $this->assertSame(EmailMapStatsController::MAP_OPTIONS, $result);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function getStats(): array
    {
        return [
            'clicked_through_count' => [
                [
                    'clicked_through_count' => '4',
                    'country'               => '',
                ],
                [
                    'clicked_through_count' => '7',
                    'country'               => 'Italy',
                ],
            ],
            'read_count' => [
                [
                    'read_count'            => '4',
                    'country'               => '',
                ],
                [
                    'read_count'            => '12',
                    'country'               => 'Italy',
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function testGetData(): void
    {
        $email = new Email();
        $email->setName('Test email');

        $dateFrom = new \DateTimeImmutable('2023-07-20');
        $dateTo   = new \DateTimeImmutable('2023-07-25');

        $this->emailModelMock->method('getCountryStats')
            ->with($email, $dateFrom, $dateTo, false)
            ->willReturn($this->getStats());

        $results = $this->mapController->getData($email, $dateFrom, $dateTo);

        $this->assertCount(2, $results);
        $this->assertSame($this->getStats(), $results);
    }

    /**
     * @throws \Exception
     */
    public function testViewAction(): void
    {
        $leadsPayload = [
            [
                'email'   => 'example1@test.com',
                'country' => 'Italy',
                'read'    => true,
                'click'   => true,
            ],
            [
                'email'   => 'example2@test.com',
                'country' => 'France',
                'read'    => false,
                'click'   => false,
            ],
            [
                'email'   => 'example4@test.com',
                'country' => '',
                'read'    => true,
                'click'   => true,
            ],
            [
                'email'   => 'example5@test.com',
                'country' => 'Poland',
                'read'    => true,
                'click'   => false,
            ],
            [
                'email'   => 'example6@test.com',
                'country' => 'Poland',
                'read'    => true,
                'click'   => true,
            ],
        ];

        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        foreach ($leadsPayload as $l) {
            $lead = new Lead();
            $lead->setEmail($l['email']);
            $lead->setCountry($l['country']);
            $this->em->persist($lead);

            $this->emulateEmailStat($lead, $email, $l['read']);

            if ($l['read'] && $l['click']) {
                $hits       = rand(1, 5);
                $uniqueHits = rand(1, $hits);
                $this->emulateClick($lead, $email, $hits, $uniqueHits);
            }
        }
        $this->em->flush();

        $this->client->request('GET', "s/emails-map-stats/{$email->getId()}/false/2023-07-20/2023-07-25");
        $clientResponse = $this->client->getResponse();
        $crawler        = new Crawler($clientResponse->getContent(), $this->client->getInternalRequest()->getUri());

        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        $this->assertSame('E-mails', $crawler->filter('.map-options__title')->innerText());
        $this->assertCount(1, $crawler->filter('div.map-options'));
        $this->assertCount(1, $crawler->filter('div.vector-map'));

        $readOption = $crawler->filter('label.map-options__item')->filter('[data-stat-unit="Read"]');
        $this->assertCount(1, $readOption);
        $this->assertSame('Total: 4 (3 with country)', $readOption->attr('data-legend-text'));
        $this->assertSame('{"IT":1,"PL":2}', $readOption->attr('data-map-series'));

        $clickOption = $crawler->filter('label.map-options__item')->filter('[data-stat-unit="Click"]');
        $this->assertCount(1, $clickOption);
        $this->assertSame('Total: 3 (2 with country)', $clickOption->attr('data-legend-text'));
        $this->assertSame('{"IT":1,"PL":1}', $clickOption->attr('data-map-series'));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateEmailStat(Lead $lead, Email $email, bool $isRead): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('test@test.com');
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime('2023-07-21'));
        $stat->setEmail($email);
        $stat->setIsRead($isRead);
        $this->em->persist($stat);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    private function emulateClick(Lead $lead, Email $email, int $hits, int $uniqueHits): void
    {
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('127.0.0.1');
        $this->em->persist($ipAddress);
        $this->em->flush();

        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl('https://example.com');
        $redirect->setHits($hits);
        $redirect->setUniqueHits($uniqueHits);
        $this->em->persist($redirect);

        $trackable = new Trackable();
        $trackable->setChannelId($email->getId());
        $trackable->setChannel('email');
        $trackable->setHits($hits);
        $trackable->setUniqueHits($uniqueHits);
        $trackable->setRedirect($redirect);
        $this->em->persist($trackable);

        $pageHit = new Hit();
        $pageHit->setRedirect($redirect);
        $pageHit->setIpAddress($ipAddress);
        $pageHit->setEmail($email);
        $pageHit->setLead($lead);
        $pageHit->setDateHit(new \DateTime());
        $pageHit->setCode(200);
        $pageHit->setUrl($redirect->getUrl());
        $pageHit->setTrackingId($redirect->getRedirectId());
        $pageHit->setSource('email');
        $pageHit->setSourceId($email->getId());
        $this->em->persist($pageHit);
    }
}
