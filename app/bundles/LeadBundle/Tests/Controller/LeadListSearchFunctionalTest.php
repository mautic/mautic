<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\Bundle\DoctrineBundle\Twig\DoctrineExtension;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class LeadListSearchFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var mixed[]
     */
    protected array $clientOptions = ['debug' => true];

    /** @noinspection SqlResolve */
    public function testSegmentSearch(): void
    {
        // create some leads
        $leadOne   = $this->createLead('one');
        $leadTwo   = $this->createLead('two');
        $leadThree = $this->createLead('three');
        $leadFour  = $this->createLead('four');
        $leadFive  = $this->createLead('five');
        $leadSix   = $this->createLead('six');

        // add some leads in lists
        $listOne  = $this->createLeadList('first-list', $leadOne, $leadTwo, $leadThree);
        $listTwo  = $this->createLeadList('second-list', $leadOne, $leadFour, $leadFive, $leadSix);

        $this->em->flush();
        $this->em->clear();

        $this->client->enableProfiler();
        $prefix          = static::getContainer()->getParameter('mautic.db_table_prefix');
        $previousQueries = [];

        // non-existent segment search
        $this->assertSearchResult('segment%3AnonExistent', [], [$leadOne, $leadTwo, $leadThree, $leadFour, $leadFive, $leadSix]);
        $this->assertQueries([
            "SELECT list.id FROM {$prefix}lead_lists list WHERE list.alias = 'nonexistent'",
            "SELECT COUNT(l.id) as count FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN (0)))) AND (l.date_identified IS NOT NULL)",
        ], $previousQueries);

        // first-list segment search
        $this->assertSearchResult('segment%3A'.$listOne->getAlias(), [$leadOne, $leadTwo, $leadThree], [$leadFour, $leadFive, $leadSix]);
        $this->assertQueries([
            "SELECT list.id FROM {$prefix}lead_lists list WHERE list.alias = '{$listOne->getAlias()}'",
            "SELECT COUNT(l.id) as count FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listOne->getId()}')))) AND (l.date_identified IS NOT NULL)",
            "SELECT l.* FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listOne->getId()}')))) AND (l.date_identified IS NOT NULL) ORDER BY l.last_active DESC, l.id DESC LIMIT 30",
        ], $previousQueries);
        $this->assertSearchResult('!segment%3A'.$listOne->getAlias(), [$leadFour, $leadFive, $leadSix], [$leadOne, $leadTwo, $leadThree]);
        $this->assertQueries([
            "SELECT list.id FROM {$prefix}lead_lists list WHERE list.alias = '{$listOne->getAlias()}'",
            "SELECT COUNT(l.id) as count FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (NOT EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listOne->getId()}')))) AND (l.date_identified IS NOT NULL)",
            "SELECT l.* FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (NOT EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listOne->getId()}')))) AND (l.date_identified IS NOT NULL) ORDER BY l.last_active DESC, l.id DESC LIMIT 30",
        ], $previousQueries);

        // second-list segment search
        $this->assertSearchResult('segment%3A'.$listTwo->getAlias(), [$leadOne, $leadFour, $leadFive, $leadSix], [$leadTwo, $leadThree]);
        $this->assertQueries([
            "SELECT list.id FROM {$prefix}lead_lists list WHERE list.alias = '{$listTwo->getAlias()}'",
            "SELECT COUNT(l.id) as count FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listTwo->getId()}')))) AND (l.date_identified IS NOT NULL)",
            "SELECT l.* FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listTwo->getId()}')))) AND (l.date_identified IS NOT NULL) ORDER BY l.last_active DESC, l.id DESC LIMIT 30",
        ], $previousQueries);
        $this->assertSearchResult('!segment%3A'.$listTwo->getAlias(), [$leadTwo, $leadThree], [$leadOne, $leadFour, $leadFive, $leadSix]);
        $this->assertQueries([
            "SELECT list.id FROM {$prefix}lead_lists list WHERE list.alias = '{$listTwo->getAlias()}'",
            "SELECT COUNT(l.id) as count FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (NOT EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listTwo->getId()}')))) AND (l.date_identified IS NOT NULL)",
            "SELECT l.* FROM {$prefix}leads l USE INDEX FOR JOIN ({$prefix}lead_date_added) WHERE (NOT EXISTS(SELECT 1 FROM {$prefix}lead_lists_leads lla WHERE (l.id = lla.lead_id) AND (lla.manually_removed = 0) AND (lla.leadlist_id IN ('{$listTwo->getId()}')))) AND (l.date_identified IS NOT NULL) ORDER BY l.last_active DESC, l.id DESC LIMIT 30",
        ], $previousQueries);
    }

    /**
     * @param Lead[] $expectedLeads
     * @param Lead[] $notExpectedLeads
     */
    private function assertSearchResult(string $search, array $expectedLeads, array $notExpectedLeads): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search='.$search);
        self::assertResponseIsSuccessful();
        $responseText = $crawler->text();

        foreach ($expectedLeads as $expectedLead) {
            Assert::assertStringContainsString($expectedLead->getEmail(), $responseText, sprintf('Lead with the email "%s" should be in the result.', $expectedLead->getEmail()));
        }

        foreach ($notExpectedLeads as $notExpectedLead) {
            Assert::assertStringNotContainsString($notExpectedLead->getEmail(), $responseText, sprintf('Lead with the email "%s" should not be in the result.', $notExpectedLead->getEmail()));
        }
    }

    /**
     * @param string[] $expectedQueries
     * @param string[] $previousQueries
     */
    private function assertQueries(array $expectedQueries, array &$previousQueries): void
    {
        /** @var DoctrineDataCollector $dbCollector */
        $dbCollector       = $this->client->getProfile()->getCollector('db');
        $allQueries        = $dbCollector->getQueries()['default'];
        $queries           = array_diff_key($allQueries, $previousQueries);
        $previousQueries   = $allQueries;
        $doctrineExtension = new DoctrineExtension();

        $queries = array_map(function (array $query) use ($doctrineExtension) {
            return $doctrineExtension->replaceQueryParameters($query['sql'], $query['params']);
        }, $queries);

        foreach ($expectedQueries as $expectedQuery) {
            $matchedQueries = array_filter($queries, function (string $query) use ($expectedQuery) {
                return $expectedQuery === $query;
            });
            Assert::assertCount(1, $matchedQueries, sprintf('The query "%s" was expected to be executed once.', $expectedQuery));
        }
    }

    /**
     * @throws ORMException
     */
    private function createLead(string $lastName): Lead
    {
        $lead = new Lead();
        $lead->setLastname($lastName);
        $lead->setEmail(sprintf('%s@mail.tld', $lastName));
        $this->em->persist($lead);

        return $lead;
    }

    /**
     * @param Lead ...$leads
     *
     * @throws ORMException
     */
    private function createLeadList(string $name, ...$leads): LeadList
    {
        $leadList = new LeadList();
        $leadList->setName($name);
        $leadList->setPublicName($name);
        $leadList->setAlias(mb_strtolower($name));
        $this->em->persist($leadList);

        foreach ($leads as $lead) {
            $this->addLeadToList($lead, $leadList);
        }

        return $leadList;
    }

    private function addLeadToList(Lead $leadOne, LeadList $sourceList): void
    {
        $listLead = new ListLead();
        $listLead->setLead($leadOne);
        $listLead->setList($sourceList);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);
    }
}
