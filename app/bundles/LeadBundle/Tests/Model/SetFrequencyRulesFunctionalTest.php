<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Model\LeadModel;

final class SetFrequencyRulesFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testSetFrequencyRulesForCategorySubscriptionUnsubscription(): void
    {
        $categoriesFlags = [
            'one'   => true,
            'two'   => false,
            'three' => true,
            'four'  => true,
            'five'  => false,
        ];

        $categories = $this->createCategories($categoriesFlags);

        $lead = $this->createLead('John', 'Doe', 'some@test.com');

        $this->em->flush();

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
        $model = static::getContainer()->get('mautic.lead.model.lead');
        $model->setFrequencyRules($lead, $data, [], []);

        $subscribedCategories   = $model->getLeadCategories($lead);
        $this->assertEmpty(array_diff($subscribedCategories, array_keys($categoriesToSubscribe)));

        // Unsubscribe categories.
        $data['global_categories'] = array_keys($categoriesToUnsubscribe);
        $model->setFrequencyRules($lead, $data, [], []);

        $unsubscribedCategories = $model->getUnsubscribedLeadCategoriesIds($lead);
        $this->assertEmpty(array_diff($unsubscribedCategories, array_keys($categoriesToSubscribe)));
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
            $categories[$suffix] = $this->createCategory($suffix, $suffix);
        }

        $this->em->flush();

        return $categories;
    }
}
