<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Service;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\BatchDeleteService;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;
use PHPUnit\Framework\MockObject\MockObject;

class BatchDeleteServiceTest extends MauticMysqlTestCase
{
    private BatchDeleteService $batchDeleteService;
    /**
     * @var MockObject&Translator
     */
    private MockObject $securityMock;
    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $translatorMock;

    private CompanyModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the dependencies
        $this->securityMock       = $this->createMock(CorePermissions::class);
        $this->translatorMock     = $this->createMock(Translator::class);
        $this->batchDeleteService = new BatchDeleteService($this->securityMock, $this->translatorMock);
        // Create entities to be deleted
        $this->model = static::getContainer()->get('mautic.lead.model.company');
        $this->createEntity($this->model, Company::class, 'compA');
        $this->createEntity($this->model, Company::class, 'compB');
    }

    public function testBatchDeleteCompanies(): void
    {
        $this->securityMock->expects(self::exactly(2))
            ->method('hasEntityAccess')
            ->willReturn(true);

        $this->translatorMock->expects(self::once())
            ->method('hasId')
            ->with('mautic.lead.company.notice.batch_deleted', 'flashes')
            ->willReturn(true);

        $flashes = $this->batchDeleteService->batchDelete(
            $this->model,
            [],
            'all',
            '',
            'lead.company',
            [$this, 'isLocked'],
        );
        $successFlash = $flashes[0];

        $this->assertArrayHasKey('msg', $successFlash);
        $this->assertEquals(2, $successFlash['msgVars']['%count%']);
    }

    public function testBatchDeleteCompanyAccessDenied(): void
    {
        $this->securityMock->expects(self::exactly(2))
            ->method('hasEntityAccess')
            ->willReturnOnConsecutiveCalls(true, false);

        $flashes = $this->batchDeleteService->batchDelete(
            $this->model,
            [],
            'all',
            '',
            'lead.company',
            [$this, 'isLocked'],
        );

        $this->assertNotEmpty($flashes, 'Flashes array is empty');
        $this->assertArrayHasKey(0, $flashes, 'Flashes array does not have expected key 0');
        $this->assertEquals('mautic.core.error.accessdenied', $flashes[0]['msg']);
        $this->assertArrayHasKey(1, $flashes, 'Flashes array does not have expected key 1');
        $this->assertEquals('mautic.core.notice.batch_deleted', $flashes[1]['msg']);
    }

    private function createEntity(FormModel $model, string $entityType, string $entityName): FormEntity
    {
        $entity = (new $entityType())
            ->setName($entityName)
            ->setCreatedBy(1);
        $model->saveEntity($entity);

        return $entity;
    }

    /**
     * @return string[]
     */
    public function isLocked(): array
    {
        return [
            'type'    => 'error',
            'msg'     => 'mautic.core.error.locked',
        ];
    }
}
