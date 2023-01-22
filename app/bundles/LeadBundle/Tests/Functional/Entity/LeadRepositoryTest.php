<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

final class LeadRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @return array<array<string|null, bool|null>>
     */
    public function joinIpAddressesProvider(): array
    {
        return [
            ['' => []],
            ['' => ['joinIpAddresses' => true]],
            ['' => ['joinIpAddresses' => false]],
        ];
    }

    /**
     * @dataProvider joinIpAddressesProvider
     */
    public function testSaveIpAddressToContacts($args): void
    {
        $contactRepo = $this->em->getRepository(Lead::class);
        \assert($contactRepo instanceof LeadRepository);

        $ipRepo = $this->em->getRepository(IpAddress::class);
        \assert($ipRepo instanceof IpAddressRepository);

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
    }
}
