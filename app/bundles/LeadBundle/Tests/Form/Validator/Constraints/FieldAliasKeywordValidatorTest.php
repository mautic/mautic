<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Form\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeyword;
use Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator;
use Mautic\LeadBundle\Helper\FieldAliasHelper;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FieldAliasKeywordValidatorTest extends \PHPUnit\Framework\TestCase
{
    private $listModelMock;
    private $fieldAliasHelperlMock;
    private $executionContextMock;
    private $entityManagerMock;
    private $unitOfWorkMock;
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldAliasHelperlMock = $this->createMock(FieldAliasHelper::class);
        $this->listModelMock         = $this->createMock(ListModel::class);
        $this->executionContextMock  = $this->createMock(ExecutionContextInterface::class);
        $this->entityManagerMock     = $this->createMock(EntityManager::class);
        $this->unitOfWorkMock        = $this->createMock(UnitOfWork::class);

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

        $this->validator = new FieldAliasKeywordValidator($this->listModelMock, $this->fieldAliasHelperlMock, $this->entityManagerMock);
        $this->validator->initialize($this->executionContextMock);
    }

    public function testAddValidationFailure()
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

    public function testAddValidationSuccess()
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

    public function testEditValidationFailure()
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

    public function testEditValidationSuccess()
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

    public function testEditWithoutChangesValidationSuccess()
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
}
