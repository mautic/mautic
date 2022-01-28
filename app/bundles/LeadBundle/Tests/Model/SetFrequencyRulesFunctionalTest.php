<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

final class SetFrequencyRulesFunctionalTest extends MauticMysqlTestCase
{
    public function testSetFrequencyRulesForCategorySubscriptionUnsubscription(): void
    {
        // Create category
        $categories = $this->createCategories();
        // Create lead
        $lead = $this->createLead();

        shuffle($categories);

        // Subscribe categories.
        $categoriesToSubscribe = [];
        /** @var Category $category */
        foreach (array_slice($categories, 0, 3) as $category) {
            $categoriesToSubscribe[$category->getId()] = $category->getId();
        }

        $data = [
            'global_categories' => array_keys($categoriesToSubscribe),
            'lead_lists'        => [],
        ];

        /** @var LeadModel $model */
        $model = $this->container->get('mautic.lead.model.lead');
        $model->setFrequencyRules($lead, $data);

        $subscribedCategories   = $model->getLeadCategories($lead);
        $this->assertEmpty(array_diff($subscribedCategories, array_keys($categoriesToSubscribe)));

        // Unsubscribe categories.
        unset($categoriesToSubscribe[$category->getId()]);
        $data['global_categories'] = $categoriesToSubscribe;
        $model->setFrequencyRules($lead, $data);
        $unsubscribedCategories = $model->getLeadCategories($lead);
        $this->assertEmpty(array_diff($unsubscribedCategories, array_keys($categoriesToSubscribe)));
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
     * @return Category[]
     */
    private function createCategories(): array
    {
        $categories = [];
        foreach (['one', 'two', 'three', 'four'] as $suffix) {
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
}
