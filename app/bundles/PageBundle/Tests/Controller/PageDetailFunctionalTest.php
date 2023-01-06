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
    public function testContactListExists(int $expected, array $leads)
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

        foreach ($leads as $lead) {
            $leadModel->saveEntity($lead);

            $request  = new Request();

            $pageModel->hitPage($page, $request, '200', $lead);
        }

        $crawler = $this->client->request('GET', sprintf('/s/pages/view/%d', $page->getId()));
        $cards   = $crawler->filter('#leads-container .contact-cards');

        $expected = min($expected, $pageLimit);

        Assert::assertSame($expected, $cards->count());
    }

    /**
     * Page contact provider.
     */
    public function pageContactProvider(): iterable
    {
        // No leads of any sort hit the page, expect 0 contacts.
        yield 'no leads' => [0, []];

        // Only 10 anonymous (unidentified) leads have hit the page, expect to
        // see 0 leads.
        yield 'only anons' => [0, $this->getAnonymousLeads(10)];

        // 5 identified leads have hit the page, expect to see them all.
        yield '5 leads' => [5, $this->getIdentifiedLeads(5, 1)];

        // Of the four leads on the page, only expect the 2 identified leads.
        yield '2 leads, 2 anons' => [2, array_merge(
            $this->getIdentifiedLeads(2, 6),
            $this->getAnonymousLeads(2)
        )];

        // With 40 identified leads hitting the page, expect to see a full
        // pagination result. The $expected value of 40 here sets the upper
        // bound of expected leads, which may be adjusted to the per-page
        // count from the PageHelper (default_pagelimit: 30).
        yield 'two pages' => [40, $this->getIdentifiedLeads(40, 8)];

        // With groups of 10 identified leads on either side of a block of 100
        // anonymous visitors, expect that the set of leads displayed in
        // pagination will be the entire set of 20 identified leads. The
        // maximum page size is 100, so this quantity of data ensures that
        // pagination is unaffected by omitted leads.
        yield '20 leads, 100 anons' => [20, array_merge(
            $this->getIdentifiedLeads(10, 50),
            $this->getAnonymousLeads(100),
            $this->getIdentifiedLeads(10, 60),
        )];
    }

    /**
     * Get an array containing specified number of identified leads.
     *
     * @param int $count How many leads to create
     * @param int $start Optional starting value for numeric suffix in Lead properties
     */
    private function getIdentifiedLeads(int $count, int $start = 0): array
    {
        $leads = [];
        for ($i = $start; $i < $start + $count; ++$i) {
            $leads[] = (new Lead())
                ->setFirstname('Test'.$i)
                ->setLastname('PageTest'.$i)
                ->setEmail('test'.$i.'@example.com');
        }

        return $leads;
    }

    /**
     * Get anonymous leads.
     *
     * @param int $count Number of anonymous leads to return
     */
    private function getAnonymousLeads(int $count): array
    {
        $leads = [];
        for ($i = 0; $i < $count; ++$i) {
            $leads[] = (new Lead());
        }

        return $leads;
    }
}
