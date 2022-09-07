<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine\Paginator;

use function assert;
use Doctrine\DBAL\Logging\DebugStack;
use Mautic\CoreBundle\Doctrine\Paginator\SimplePaginator;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class SimplePaginatorTest extends MauticMysqlTestCase
{
    /**
     * Enable debug for enabling DBAL query logger.
     *
     * @var array<string,mixed>
     */
    protected array $clientOptions = ['debug' => true];

    public function testPaginator(): void
    {
        $ipAddress1 = new IpAddress('10.0.0.1');
        $ipAddress2 = new IpAddress('10.0.0.2');
        $ipAddress3 = new IpAddress('10.0.0.3');

        $this->em->persist($ipAddress1);
        $this->em->persist($ipAddress2);
        $this->em->persist($ipAddress3);
        $this->em->flush();

        $repository = $this->em->getRepository(IpAddress::class);
        assert($repository instanceof IpAddressRepository);

        $paginator  = $repository->getEntities([
            'use_simple_paginator' => true,
            'start'                => 1,
            'limit'                => 5,
            'orderBy'              => $repository->getTableAlias().'.id',
        ]);

        $this->assertInstanceOf(SimplePaginator::class, $paginator);
        $this->assertCount(3, $paginator, 'The total count should be 3.');
        $this->assertCount(3, $paginator, 'The total count should be 3. Running it again to test lazy-loading.');
        $this->assertSame([
            $ipAddress2->getId() => $ipAddress2,
            $ipAddress3->getId() => $ipAddress3,
        ], iterator_to_array($paginator), 'Only 2 last records should be returned.');

        $logger = self::$container->get('doctrine.dbal.logger.profiling.default');
        assert($logger instanceof DebugStack);

        $this->assertCount(6, $logger->queries, 'There should be exactly 6 queries executed.');
        $this->assertSame('SELECT count(m0_.id) AS sclr_0 FROM mautic_ip_addresses m0_', $logger->queries[5]['sql'], 'Simple paginator should not use either a DISTINCT keyword or sub-queries.');
        $this->assertSame('SELECT m0_.id AS id_0, m0_.ip_address AS ip_address_1, m0_.ip_details AS ip_details_2 FROM mautic_ip_addresses m0_ ORDER BY m0_.id ASC LIMIT 5 OFFSET 1', $logger->queries[6]['sql'], 'Ordering and limit/offset have to be reflected.');
    }
}
