<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Request;

class LeadCategoryRepositoryFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    /**
     * @var array<string, bool>
     */
    private $categoryFlags = [
        'one'   => true,
        'two'   => false,
        'three' => true,
    ];

    public function testCategoriesOnContactPreferences(): void
    {
        $lead       = $this->createLead('John', 'Doe', 'john@doe.com');

        $categories = $this->createCategories();
        $this->setLeadCategories($lead, $categories);

        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/contactFrequency/'.$lead->getId());
        $response   = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        $this->assertCount(2, $subscribedCats);
    }

    public function testCategoriesOnContactPreferencesWhenNewCategoryIsAdded(): void
    {
        $lead       = $this->createLead('John', 'Doe', 'john@doe.com');

        // Add three categories to the lead.
        // Subscribed 2
        // Unsubscribed 1
        $categories = $this->createCategories();
        $this->setLeadCategories($lead, $categories);

        // Add new category, there is no association with the lead.
        $this->createCategory('Category extra', 'cat-extra');

        // Request the preference on Lead detail page.
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/contactFrequency/'.$lead->getId());
        $response   = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        // The count should be 3 as we have two subscribed and a new one.
        $this->assertCount(3, $subscribedCats);

        /** @var LeadModel $model */
        $model = self::$container->get('mautic.lead.model.lead');

        // The association count is 2.
        $this->assertCount(2, $model->getLeadCategories($lead));

        // Now submit the preference form.
        $form = $crawler->filterXPath('//form[@name="lead_contact_frequency_rules"]')->form();
        $this->client->submit($form);

        // The association count is bumped up to three.
        $this->assertCount(3, $model->getLeadCategories($lead));
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
    }
}
