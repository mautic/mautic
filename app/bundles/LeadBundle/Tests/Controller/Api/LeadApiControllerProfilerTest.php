<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\Common\Cache\CacheProvider;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that enable debug and profiler to test performance optimizations.
 * These tests are slower as debug and profiler are enabled. Add tests here only if you need profiler.
 */
final class LeadApiControllerProfilerTest extends MauticMysqlTestCase
{
    /**
     * @var array<string,mixed>
     */
    protected array $clientOptions = ['debug' => true];

    protected function setUp(): void
    {
        // Disable API just for specific test.
        $this->configParams['api_enabled'] = true;

        parent::setUp();
    }

    public function testGetContacts(): void
    {
        // reset result cache if any
        $cache = $this->em->getConfiguration()->getResultCacheImpl();

        if ($cache instanceof CacheProvider) {
            $cache = clone $cache;
            $cache->setNamespace('leadCount');
            $cache->deleteAll();
        }

        for ($i = 0; $i < 11; ++$i) {
            $contact = new Lead();
            $contact->setEmail("contact{$i}@email.com");
            $contact->setFirstname('Contact');
            $contact->setLastname($i);
            $this->em->persist($contact);
        }

        $this->em->flush();

        $this->client->enableProfiler();

        // Make 2 requests to see how many count queries we'll get.
        $this->getContacts();
        $this->getContacts();

        // Without the cache, there would be 2 COUNT queries. With the cache, there is just one.
        Assert::assertCount(1, $this->findCountQueries());
    }

    private function getContacts(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/contacts');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertSame(11, (int) $response['total']);
        Assert::assertCount(11, $response['contacts']);
    }

    /**
     * @return array<mixed[]>
     */
    private function findCountQueries(): array
    {
        /** @var DoctrineDataCollector $dbCollector */
        $dbCollector = $this->client->getProfile()->getCollector('db');
        $allQueries  = $dbCollector->getQueries()['default'];

        return array_filter(
            $allQueries,
            fn (array $query) => 'SELECT COUNT(l.id) as count FROM '.MAUTIC_TABLE_PREFIX.'leads l' === $query['sql']
        );
    }
}
