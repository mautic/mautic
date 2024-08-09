<?php

namespace Mautic\LeadBundle\Tests\Form\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeyword;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator;
use Mautic\LeadBundle\Helper\FieldAliasHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldAliasKeywordValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactSegmentFilterDictionary|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $contactSegmentFilterDictionary;

    private \PHPUnit\Framework\MockObject\MockObject $listModelMock;

    private \PHPUnit\Framework\MockObject\MockObject $fieldAliasHelperlMock;

    private \PHPUnit\Framework\MockObject\MockObject $executionContextMock;

    private \PHPUnit\Framework\MockObject\MockObject $entityManagerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translatorMock;

    private \PHPUnit\Framework\MockObject\MockObject $unitOfWorkMock;

    private FieldAliasKeywordValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldAliasHelperlMock          = $this->createMock(FieldAliasHelper::class);
        $this->listModelMock                  = $this->createMock(ListModel::class);
        $this->executionContextMock           = $this->createMock(ExecutionContextInterface::class);
        $this->entityManagerMock              = $this->createMock(EntityManager::class);
        $this->unitOfWorkMock                 = $this->createMock(UnitOfWork::class);
        $this->translatorMock                 = $this->createMock(TranslatorInterface::class);
        $this->contactSegmentFilterDictionary = $this->createMock(ContactSegmentFilterDictionary::class);

        $this->entityManagerMock
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWorkMock);

        $this->listModelMock->method('getChoiceFields')
            ->willReturn(
                [
                    'lead' => [
                        'date_added' => [
                            'label'      => 'mautic.core.date.added',
                            'properties' => ['type' => 'date'],
                            'operators'  => 'default',
                            'object'     => 'lead',
                        ],
                        'date_identified' => [
                            'label'      => 'mautic.lead.list.filter.date_identified',
                            'properties' => ['type' => 'date'],
                            'operators'  => 'default',
                            'object'     => 'lead',
                        ],
                    ],
                ]
            );

        $this->contactSegmentFilterDictionary->method('getFilters')->willReturn(
            []
        );

        $this->translatorMock->method('trans')->willReturn('');

        $this->validator = new FieldAliasKeywordValidator(
            $this->listModelMock,
            $this->fieldAliasHelperlMock,
            $this->entityManagerMock,
            $this->translatorMock,
            $this->contactSegmentFilterDictionary
        );
        $this->validator->initialize($this->executionContextMock);
    }

    public function testAddValidationFailure(): void
    {
        $originalField = [];

        $this->unitOfWorkMock
            ->method('getOriginalEntityData')
            ->willReturn($originalField);

        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('date_added');

        $this->executionContextMock->expects($this->once())->method('addViolation')->with('mautic.lead.field.keyword.invalid');

        $this->validator->validate($field, new FieldAliasKeyword());
    }

    public function testAddValidationSuccess(): void
    {
        $originalField = [];

        $this->unitOfWorkMock
            ->method('getOriginalEntityData')
            ->willReturn($originalField);

        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('not_keyword');

        $this->executionContextMock->expects($this->never())->method('addViolation');

        $this->validator->validate($field, new FieldAliasKeyword());
    }

    public function testEditValidationFailure(): void
    {
        $originalField = [
            'alias' => 'old_alias',
        ];

        $this->unitOfWorkMock
            ->method('getOriginalEntityData')
            ->willReturn($originalField);

        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('date_added');

        $this->executionContextMock->expects($this->once())->method('addViolation')->with('mautic.lead.field.keyword.invalid');

        $this->validator->validate($field, new FieldAliasKeyword());
    }

    public function testEditValidationSuccess(): void
    {
        $originalField = [
            'alias' => 'old_alias',
        ];

        $this->unitOfWorkMock
            ->method('getOriginalEntityData')
            ->willReturn($originalField);

        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('not_keyword');

        $this->executionContextMock->expects($this->never())->method('addViolation');

        $this->validator->validate($field, new FieldAliasKeyword());
    }

    public function testEditWithoutChangesValidationSuccess(): void
    {
        $originalField = [
            'alias' => 'date_added',
        ];

        $this->unitOfWorkMock
            ->method('getOriginalEntityData')
            ->willReturn($originalField);

        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('date_added');

        $this->executionContextMock->expects($this->never())->method('addViolation');

        $this->validator->validate($field, new FieldAliasKeyword());
    }

    public function testFailureReservedKeyWords(): void
    {
        $originalFields = [
            'alias' => 'old_alias',
        ];

        $this->unitOfWorkMock
            ->method('getOriginalEntityData')
            ->willReturn($originalFields);

        $this->executionContextMock->expects($this->once())->method('addViolation');

        $field = new LeadField();
        $field->setObject('lead');
        $field->setAlias('contact_id');

        $this->validator->validate($field, new FieldAliasKeyword());
    }
}
