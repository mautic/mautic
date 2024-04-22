<?php

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use PHPUnit\Framework\Assert;

class PageModelFunctionalTest extends MauticMysqlTestCase
{
    private PageModel $pageModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pageModel = self::$container->get('mautic.page.model.page');
    }

    public function testMostVisitedPagesReport(): void
    {
        $page = new Page();
        $page->setTitle('test page');
        $page->setAlias('test_page');

        $this->em->persist($page);
        $this->em->flush();

        $hit = new Hit();
        $hit->setDateHit(new \DateTime());
        $hit->setCode(200);
        $hit->setTrackingId(hash('sha1', uniqid('mt_rand()', true)));
        $hit->setIpAddress(new IpAddress('127.0.0.1'));
        $hit->setPage($page);

        $this->em->persist($hit);
        $this->em->flush();

        $hit = new Hit();
        $hit->setDateHit(new \DateTime());
        $hit->setCode(200);
        $hit->setTrackingId(hash('sha1', uniqid('mt_rand()', true)));
        $hit->setIpAddress(new IpAddress('127.0.0.1'));

        $this->em->persist($hit);
        $this->em->flush();

        $query = $this->em->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph');
        $query->leftJoin('ph', MAUTIC_TABLE_PREFIX.'pages', 'p', 'ph.page_id = p.id');

        $res = $this->pageModel->getHitRepository()->getMostVisited($query);

        foreach ($res as $hit) {
            Assert::assertNotNull($hit['id']);
            Assert::assertNotNull($hit['title']);
            Assert::assertNotNull($hit['hits']);
        }
    }
}
