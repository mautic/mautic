<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\LeadBundle\Entity\LeadList;

class ListControllerTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $list = $this->createList();

        $this->em->persist($list);
        $this->em->flush();
        $this->em->clear();

        $urlAlias   = 'segments';
        $routeAlias = 'leadlist';
        $column     = 'dateModified';
        $column2    = 'name';
        $tableAlias = 'l.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    /**
     * Check if list contains correct values.
     */
    public function testViewList(): void
    {
        $list = $this->createList();
        $list->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $list->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $list->setCreatedByUser('Test User');

        $this->em->persist($list);
        $this->em->flush();
        $this->em->clear();

        $this->client->request('GET', '/s/segments');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('February 7, 2020', $clientResponse->getContent());
        $this->assertStringContainsString('March 21, 2020', $clientResponse->getContent());
        $this->assertStringContainsString('Test User', $clientResponse->getContent());
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/segments?search=has%3Aresults&tmpl=list');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
    }

    /**
     * Create a list.
     */
    private function createList(string $suffix = 'A'): LeadList
    {
        $list = new LeadList();
        $list->setName("Segment $suffix");
        $list->setPublicName("Segment $suffix");
        $list->setAlias("segment-$suffix");
        $list->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $list->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $list->setCreatedByUser('Test User');

        return $list;
    }
}
