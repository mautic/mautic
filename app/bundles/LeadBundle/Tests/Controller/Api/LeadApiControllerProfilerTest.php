<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\Common\Cache\CacheProvider;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
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
        $cache = $this->em->getConfiguration()->getResultCache();

        if ($cache instanceof CacheProvider) {
            $cache = clone $cache;
            $cache->setNamespace('leadCount');
            $cache->deleteAll();
        }

        for ($i = 0; $i < 11; ++$i) {
            $contact = new Lead();
            $contact->setEmail("contact{$i}@email.com");
            $contact->setPoints($i);
            $this->em->persist($contact);
        }

        $this->em->flush();

        $this->client->enableProfiler();

        // Make 4 requests to see how many count queries we'll get.
        $this->getContacts(11);
        $this->getContacts(11);
        $this->getContacts(5, ['where' => [['col' => 'l.points', 'expr' => 'lt', 'val' => 5]]]);
        $this->getContacts(5, ['where' => [['col' => 'l.points', 'expr' => 'lt', 'val' => 5]]]);

        // Without the cache, there would be 4 COUNT queries. With the cache, there is just one.
        Assert::assertCount(2, $this->findCountQueries());
    }

    /**
     * @param array<string,mixed> $queryParams
     */
    private function getContacts(int $expectedCount, array $queryParams = []): void
    {
        // We have to reset the param counter to emulate 2 requests otherwise the counter will cause the queries to be different.
        $leadRepository = $this->em->getRepository(Lead::class);
        \assert($leadRepository instanceof LeadRepository);
        $reflection = new \ReflectionClass($leadRepository);
        $counter    = $reflection->getProperty('lastUsedParameterId');
        $counter->setAccessible(true);
        $counter->setValue($leadRepository, 0);

        $this->client->request(Request::METHOD_GET, '/api/contacts', $queryParams);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        Assert::assertSame($expectedCount, (int) $response['total']);
        Assert::assertCount($expectedCount, $response['contacts']);
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
            fn (array $query) => str_starts_with($query['sql'], 'SELECT COUNT(l.id) as count FROM '.MAUTIC_TABLE_PREFIX.'leads l')
        );
    }
}
