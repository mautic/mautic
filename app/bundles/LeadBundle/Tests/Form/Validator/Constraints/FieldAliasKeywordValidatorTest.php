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
     * @var \PHPUnit\Framework\MockObject\MockObject&ExecutionContextInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $executionContextMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UnitOfWork
     */
    private \PHPUnit\Framework\MockObject\MockObject $unitOfWorkMock;

    private FieldAliasKeywordValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldAliasHelperlMock                = $this->createMock(FieldAliasHelper::class);
        $listModelMock                        = $this->createMock(ListModel::class);
        $this->executionContextMock           = $this->createMock(ExecutionContextInterface::class);
        $entityManagerMock                    = $this->createMock(EntityManager::class);
        $this->unitOfWorkMock                 = $this->createMock(UnitOfWork::class);
        $translatorMock                       = $this->createMock(TranslatorInterface::class);
        $contactSegmentFilterDictionary       = $this->createMock(ContactSegmentFilterDictionary::class);

        $entityManagerMock
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWorkMock);

        $listModelMock->method('getChoiceFields')
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

        $contactSegmentFilterDictionary->method('getFilters')->willReturn(
            []
        );

        $translatorMock->method('trans')->willReturn('');

        $this->validator = new FieldAliasKeywordValidator(
            $listModelMock,
            $fieldAliasHelperlMock,
            $entityManagerMock,
            $translatorMock,
            $contactSegmentFilterDictionary
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
