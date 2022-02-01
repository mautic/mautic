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
        $categoriesFlags = [
            'one'   => true,
            'two'   => false,
            'three' => true,
            'four'  => true,
            'five'  => false,
        ];

        // Create category
        $categories = $this->createCategories($categoriesFlags);

        // Create lead
        $lead = $this->createLead();

        // Subscribe categories.
        $categoriesToSubscribe   = [];
        $categoriesToUnsubscribe = [];
        foreach ($categories as $category) {
            $categoriesToSubscribe[$category->getId()] = $category->getId();
            if (!$categoriesFlags[$category->getTitle()]) {
                $categoriesToUnsubscribe[$category->getId()] = $category->getId();
            }
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
        $data['global_categories'] = array_keys($categoriesToUnsubscribe);
        $model->setFrequencyRules($lead, $data);
        $unsubscribedCategories = $model->getUnsubscribedLeadCategoriesIds($lead);
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
     * @param mixed[] $cats
     *
     * @return Category[]
     */
    private function createCategories(array $cats): array
    {
        $categories = [];
        foreach ($cats as $suffix => $flag) {
            $category = new Category();
            $category->setTitle($suffix);
            $category->setAlias('category-'.$suffix);
            $category->setBundle('global');
            $this->em->persist($category);
            $categories[$suffix] = $category;
        }

        $this->em->flush();

        return $categories;
    }
}
