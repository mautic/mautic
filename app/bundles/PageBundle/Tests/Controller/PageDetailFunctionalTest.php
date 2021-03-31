<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class PageDetailFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * Test contact list exists for page.
     *
     * @dataProvider pageContactProvider
     */
    public function testContactListExists(int $leadCount)
    {
        $container = $this->getContainer();
        $pageModel = $container->get('mautic.page.model.page');
        $leadModel = $container->get('mautic.lead.model.lead');

        // Get per-page pagination limit from pageHelper to limit expected
        // visible number of contacts to actual number displayed at a time.
        $pageHelperFactory = $container->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFactory->make('mautic.page', 1);
        $pageLimit         = $pageHelper->getLimit();

        $page = (new Page())->setTitle(uniqid());

        $pageModel->saveEntity($page);

        for ($i = 0; $i < $leadCount; ++$i) {
            $lead = (new Lead())
                ->setFirstname('Test'.$i)
                ->setLastname('PageTest'.$i)
                ->setEmail('test'.$i.'@example.com');

            $leadModel->saveEntity($lead);

            $request  = new Request();

            $pageModel->hitPage($page, $request, '200', $lead);
        }

        $crawler = $this->client->request('GET', sprintf('/s/pages/view/%d', $page->getId()));
        $cards   = $crawler->filter('#leads-container .contact-cards');

        $expected = min($leadCount, $pageLimit);

        Assert::assertSame($expected, $cards->count());
    }

    /**
     * Page contact provider.
     *
     * @return array
     */
    public function pageContactProvider(): iterable
    {
        yield 'no leads'  => [0];
        yield '5 leads'   => [5];
        yield 'two pages' => [40];
    }
}
