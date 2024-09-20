<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class LeadCategoryRepositoryFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    /**
     * @var array<string, bool>
     */
    private array $categoryFlags = [
        'one'   => true,
        'two'   => false,
        'three' => true,
    ];

    private ?LeadModel $model;

    private Lead $lead;

    /**
     * @var mixed[]
     */
    private array $categories;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = self::$container->get('mautic.lead.model.lead');
        $this->lead  = $this->createLead('John', 'Doe', 'john@doe.com');

        // Add three categories to the lead.
        // Subscribed 2
        // Unsubscribed 1
        $this->categories = $this->createCategories();
        $this->setLeadCategories($this->lead, $this->categories);
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories', 'lead_categories']);
    }

    public function testCategoriesOnContactPreferences(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/contactFrequency/'.$this->lead->getId());
        $response   = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        $this->assertCount(2, $subscribedCats, $crawler->html());
    }

    public function testCategoriesOnContactPreferencesWhenNewCategoryIsAdded(): void
    {
        // Add new category, there is no association with the lead.
        $this->createCategory('Category extra', 'cat-extra');

        // Request the preference on Lead detail page.
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/contacts/contactFrequency/'.$this->lead->getId());
        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        // The count should be 3 as we have two subscribed and a new one.
        $this->assertCount(3, $subscribedCats);

        // The association count is 2.
        $this->assertCount(2, $this->model->getLeadCategories($this->lead));

        // Now submit the preference form.
        $form = $crawler->filterXPath('//form[@name="lead_contact_frequency_rules"]')->form();
        $this->client->submit($form);

        // The association count is bumped up to three.
        $this->assertCount(3, $this->model->getLeadCategories($this->lead));
    }

    public function testCategoriesOnContactPreferencesWhenNewCategoryIsAddedAndNotSubscribed(): void
    {
        $categoryExtra = $this->createCategory('Category extra', 'cat-extra');

        $crawler = $this->getContactFrequencyCrawler($this->lead);

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        // The count should be 3 as we have two subscribed and a new one.
        $this->assertCount(3, $subscribedCats);

        // The association count is 2.
        $this->assertCount(2, $this->model->getLeadCategories($this->lead));

        // Now submit the preference form.
        $form = $crawler->filterXPath('//form[@name="lead_contact_frequency_rules"]')->form();
        $form->setValues(
            [
                'lead_contact_frequency_rules[global_categories]' => [
                    $this->categories['one']->getId(),
                    $this->categories['three']->getId(),
                ],
            ]
        );
        $this->client->submit($form);

        $subscribed   = $this->model->getLeadCategories($this->lead);
        $unSubscribed = $this->model->getUnsubscribedLeadCategoriesIds($this->lead);

        $this->assertCount(2, $subscribed);
        $this->assertCount(2, $unSubscribed);

        $this->assertArrayHasKey($this->categories['one']->getId(), $subscribed);
        $this->assertArrayHasKey($this->categories['three']->getId(), $subscribed);

        $this->assertArrayHasKey($this->categories['two']->getId(), $unSubscribed);
        $this->assertArrayHasKey($categoryExtra->getId(), $unSubscribed);
    }

    public function testCategoriesOnContactPreferencesWhenNewCategoryIsAddedAndExistingUnsbscribed(): void
    {
        // Add new category, there is no association with the lead.
        $categoryExtra = $this->createCategory('Category extra', 'cat-extra');

        $crawler = $this->getContactFrequencyCrawler($this->lead);

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        // The count should be 3 as we have two subscribed and a new one.
        $this->assertCount(3, $subscribedCats);

        // The association count is 2.
        $this->assertCount(2, $this->model->getLeadCategories($this->lead));

        // Now submit the preference form.
        $form = $crawler->filterXPath('//form[@name="lead_contact_frequency_rules"]')->form();
        $form->setValues(
            [
                'lead_contact_frequency_rules[global_categories]' => [
                    $categoryExtra->getId(),
                    $this->categories['three']->getId(),
                ],
            ]
        );
        $this->client->submit($form);

        $subscribed   = $this->model->getLeadCategories($this->lead);
        $unSubscribed = $this->model->getUnsubscribedLeadCategoriesIds($this->lead);

        $this->assertArrayHasKey($this->categories['three']->getId(), $subscribed);
        $this->assertArrayHasKey($categoryExtra->getId(), $subscribed);

        $this->assertArrayHasKey($this->categories['one']->getId(), $unSubscribed);
        $this->assertArrayHasKey($this->categories['two']->getId(), $unSubscribed);
    }

    public function testCategoriesOnContactPreferencesWhenUnSubscribedCategoryIsSelectedToSubscribe(): void
    {
        $crawler = $this->getContactFrequencyCrawler($this->lead);

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        // The count should be 3 as we have two subscribed and a new one.
        $this->assertCount(2, $subscribedCats);

        // The association count is 2.
        $this->assertCount(2, $this->model->getLeadCategories($this->lead));

        // Now submit the preference form.
        $form = $crawler->filterXPath('//form[@name="lead_contact_frequency_rules"]')->form();
        $form->setValues(
            [
                'lead_contact_frequency_rules[global_categories]' => [
                    $this->categories['one']->getId(),
                    $this->categories['two']->getId(),
                    $this->categories['three']->getId(),
                ],
            ]
        );
        $this->client->submit($form);

        $this->assertCount(3, $this->model->getLeadCategories($this->lead));
        $this->assertCount(0, $this->model->getUnsubscribedLeadCategoriesIds($this->lead));
    }

    /**
     * @return mixed[]
     */
    private function createCategories(): array
    {
        $categories = [];
        foreach ($this->categoryFlags as $suffix => $name) {
            $categories[$suffix] = $this->createCategory('Category '.$suffix, 'category '.$suffix);
        }

        $this->em->flush();

        return $categories;
    }

    /**
     * @param mixed[] $categories
     */
    private function setLeadCategories(Lead $lead, array $categories): void
    {
        foreach ($this->categoryFlags as $key => $flag) {
            $this->createLeadCategory($lead, $categories[$key], $flag);
        }

        $this->em->flush();
        $this->em->clear();
    }

    private function getContactFrequencyCrawler(Lead $lead): Crawler
    {
        // Request the preference on Lead detail page.
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/contacts/contactFrequency/'.$lead->getId());
        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        return $crawler;
    }
}
