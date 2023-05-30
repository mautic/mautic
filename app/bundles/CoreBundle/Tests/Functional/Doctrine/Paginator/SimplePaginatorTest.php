<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine\Paginator;

use Mautic\CoreBundle\Doctrine\Paginator\SimplePaginator;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class SimplePaginatorTest extends MauticMysqlTestCase
{
    /**
     * Enable debug for enabling DBAL query logger.
     *
     * @var array<string,mixed>
     */
    protected array $clientOptions = ['debug' => true];

    protected function setUp(): void
    {
        parent::setUp();
    }

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
    }
}
