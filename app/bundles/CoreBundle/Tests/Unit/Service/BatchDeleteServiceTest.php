<?php

namespace Mautic\CoreBundle\Tests\Unit\Service;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\BatchDeleteService;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\LeadBundle\Entity\Company;

class BatchDeleteServiceTest extends MauticMysqlTestCase
{
    private BatchDeleteService $batchDeleteService;
    private CorePermissions $securityMock;
    private Translator $translatorMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the dependencies
        $this->securityMock       = $this->createMock(CorePermissions::class);
        $this->translatorMock     = $this->createMock(Translator::class);
        $this->batchDeleteService = new BatchDeleteService($this->securityMock, $this->translatorMock);
    }

    public function testBatchDeleteCompanies(): void
    {
        $companyA = $this->createEntity(Company::class);
        $companyB = $this->createEntity(Company::class);

        $model = $this->createMock(CompanyModel::class);
    }

    public function testBatchDeleteDynamicContent(): void
    {
        $dcA = $this->createEntity(DynamicContent::class);
        $dcB = $this->createEntity(DynamicContent::class);

        $model = $this->createMock(DynamicContentModel::class);
    }

    private function createEntity(FormEntity $entityName): FormEntity
    {
        $entity = new $entityName();

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }
}
