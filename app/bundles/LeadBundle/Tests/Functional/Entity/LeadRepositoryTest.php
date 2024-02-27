<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Request;

final class LeadRepositoryTest extends MauticMysqlTestCase
{
    public function setUp(): void
    {
        $this->clientOptions = ['debug' => true];

        parent::setUp();
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public static function joinIpAddressesProvider(): array
    {
        return [
            ['' => []],
            ['' => ['joinIpAddresses' => true]],
            ['' => ['joinIpAddresses' => false]],
        ];
    }

    /**
     * @dataProvider joinIpAddressesProvider
     *
     * @param array<string, bool> $args
     */
    public function testSaveIpAddressToContacts($args): void
    {
        $contactRepo = $this->em->getRepository(Lead::class);

        $ipRepo = $this->em->getRepository(IpAddress::class);

        $ip      = new IpAddress('127.0.0.1');
        $contact = new Lead();
        $contact->addIpAddress($ip);
        $this->em->persist($contact);
        $this->em->persist($ip);
        $this->em->flush();

        $q       = $contactRepo->getEntitiesOrmQueryBuilder('(CASE WHEN u.id=1 THEN 1 ELSE 2 END) AS HIDDEN ORD', $args);
        $results = $q->getQuery()
        ->getResult();

        /** @var Lead $r */
        foreach ($results as $r) {
            $ipAddresses = $r->getIpAddresses();
            $ipAddress   = $ipAddresses->first();
            $this->assertEquals($ipAddress->getIpAddress(), '127.0.0.1');
        }

        $this->client->enableProfiler();

        $this->client->request(Request::METHOD_GET, '/s/contacts');

        $profile = $this->client->getProfile();
        /** @var DoctrineDataCollector $dbCollector */
        $dbCollector = $profile->getCollector('db');
        $queries     = $dbCollector->getQueries();

        $finalQueries = array_filter(
            $queries['default'],
            fn (array $query) => str_contains($query['sql'], 'SELECT (CASE WHEN t0_.id = 1 THEN 1 ELSE 2 END)')
        );

        foreach ($finalQueries as $query) {
            if ($args['joinIpAddresses'] ?? true) {
                $this->assertStringContainsString('LEFT JOIN test_ip_addresses', $query['sql']);
            } else {
                $this->assertStringNotContainsString('LEFT JOIN test_ip_addresses', $query['sql']);
            }
        }
    }
}
