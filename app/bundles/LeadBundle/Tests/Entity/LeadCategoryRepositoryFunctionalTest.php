<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Lead;
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

        $this->assertCount(2, $subscribedCats, $crawler->html());
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
