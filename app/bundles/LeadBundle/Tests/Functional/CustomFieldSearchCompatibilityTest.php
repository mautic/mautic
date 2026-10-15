<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

final class CustomFieldSearchCompatibilityTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testLeadSearchCommandAndCatchAllMatchTypedFields(): void
    {
        $this->createIndexedField('number_search_value', 'number', 'lead');
        $this->createIndexedField('date_search_value', 'date', 'lead');
        $this->createIndexedField('boolean_search_value', 'boolean', 'lead');

        $lead = (new Lead())
            ->setFirstname('Typed search contact')
            ->setLastname('One')
            ->setEmail('typed-search-contact@example.test')
            ->setCompany('Typed Search Inc')
            ->addUpdatedField('number_search_value', '748392')
            ->addUpdatedField('date_search_value', '2222-11-11')
            ->addUpdatedField('boolean_search_value', '1');

        self::getContainer()->get(LeadModel::class)->saveEntity($lead);

        $falseLead = (new Lead())
            ->setFirstname('False boolean contact')
            ->setLastname('One')
            ->setEmail('false-boolean-contact@example.test')
            ->addUpdatedField('boolean_search_value', '0');
        self::getContainer()->get(LeadModel::class)->saveEntity($falseLead);

        $repository = self::getContainer()->get(LeadModel::class)->getRepository();

        foreach (['number_search_value:748392', 'date_search_value:2222-11-11', 'boolean_search_value:1', '748392', '2222-11-11', '1'] as $filter) {
            $results = $repository->getEntities([
                'filter'           => $filter,
                'ignore_paginator' => true,
            ]);

            $this->assertCount(1, $results, $filter);
            $this->assertSame('Typed search contact', array_values($results)[0]->getFirstname(), $filter);
        }

        $results = $repository->getEntities(['filter' => 'boolean_search_value:0', 'ignore_paginator' => true]);
        $this->assertCount(1, $results);
        $this->assertSame('False boolean contact', array_values($results)[0]->getFirstname());
    }

    public function testCompanySearchCommandAndLookupsMatchTypedFields(): void
    {
        $this->createIndexedField('number_search_value', 'number', 'company');
        $this->createIndexedField('date_search_value', 'date', 'company');
        $this->createIndexedField('boolean_search_value', 'boolean', 'company');

        $company = (new Company())
            ->setName('Typed Search Company')
            ->setEmail('typed-search-company@example.test')
            ->addUpdatedField('number_search_value', '849203')
            ->addUpdatedField('date_search_value', '3333-11-11')
            ->addUpdatedField('boolean_search_value', '1');

        self::getContainer()->get(CompanyModel::class)->saveEntity($company);

        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get(CompanyModel::class);
        $falseCompany = (new Company())
            ->setName('False Boolean Company')
            ->setEmail('false-boolean-company@example.test')
            ->addUpdatedField('boolean_search_value', '0');
        $companyModel->saveEntity($falseCompany);

        $repository = $companyModel->getRepository();

        foreach (['number_search_value:849203', 'date_search_value:3333-11-11', 'boolean_search_value:1', '849203', '3333-11-11', '1'] as $filter) {
            $results = $repository->getEntities([
                'filter'           => $filter,
                'ignore_paginator' => true,
            ]);

            $this->assertCount(1, $results, $filter);
            $this->assertSame('Typed Search Company', array_values($results)[0]->getName(), $filter);
        }

        $results = $repository->getEntities(['filter' => 'boolean_search_value:0', 'ignore_paginator' => true]);
        $this->assertCount(1, $results);
        $this->assertSame('False Boolean Company', array_values($results)[0]->getName());

        foreach (['number_search_value' => '849203', 'date_search_value' => '3333-11-11', 'boolean_search_value' => '1'] as $field => $value) {
            $this->assertCount(1, $companyModel->getSimpleLookupResults('companyfield', [$field, $value]));
            $this->assertCount(1, $companyModel->getLookupResults('companyfield', [$field, $value]));
        }
        $this->assertCount(1, $companyModel->getSimpleLookupResults('companyfield', ['boolean_search_value', '0']));
        $this->assertCount(1, $companyModel->getLookupResults('companyfield', ['boolean_search_value', '0']));
    }

    private function createIndexedField(string $alias, string $type, string $object): void
    {
        $field = (new LeadField())
            ->setName($alias)
            ->setAlias($alias)
            ->setObject($object)
            ->setDateAdded(new \DateTime())
            ->setDateModified(new \DateTime())
            ->setIsPublished(true)
            ->setType($type);
        $field->setGroup('core');
        $field->setIsIndex(true);

        self::getContainer()->get(FieldModel::class)->saveEntity($field);
    }
}
