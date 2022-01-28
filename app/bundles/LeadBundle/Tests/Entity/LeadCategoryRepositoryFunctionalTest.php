<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Symfony\Component\HttpFoundation\Request;

class LeadCategoryRepositoryFunctionalTest extends MauticMysqlTestCase
{
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
        $lead       = $this->createLead();
        $categories = $this->createCategories();
        $this->setLeadCategories($lead, $categories);

        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/contactFrequency/'.$lead->getId());
        $response   = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $subscribedCats = $crawler->filter('select[id="lead_contact_frequency_rules_global_categories"]')->filter('option[selected="selected"]');

        $this->assertCount(2, $subscribedCats);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $lead->setLastname('Doe');
        $lead->setEmail('john.doe@test.com');

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    /**
     * @return mixed[]
     */
    private function createCategories(): array
    {
        $categories = [];
        foreach ($this->categoryFlags as $suffix => $name) {
            $category = new Category();
            $category->setTitle('Category '.$suffix);
            $category->setAlias('category-'.$suffix);
            $category->setBundle('global');
            $this->em->persist($category);
            $categories[$suffix] = $category;
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
            $newLeadCategory = new LeadCategory();
            $newLeadCategory->setLead($lead);
            $newLeadCategory->setCategory($categories[$key]);
            $newLeadCategory->setDateAdded(new \DateTime());
            $newLeadCategory->setManuallyAdded($flag);
            $newLeadCategory->setManuallyRemoved(!$flag);
            $this->em->persist($newLeadCategory);
        }
        $this->em->flush();
    }
}
