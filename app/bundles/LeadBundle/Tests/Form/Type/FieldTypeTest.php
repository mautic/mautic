<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\Helper\IndexHelper;
use Mautic\LeadBundle\Field\IdentifierFields;
use Mautic\LeadBundle\Form\Type\FieldType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

class FieldTypeTest extends TestCase
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var IdentifierFields
     */
    private $identifierFields;

    /**
     * @var IndexHelper
     */
    private $indexHelper;

    /**
     * @var ExecutionContextInterface
     */
    private $executionContext;

    /**
     * @var FormInterface
     */
    private $formInterface;

    /**
     * @var FieldType
     */
    private $fieldType;

    /**
     * @var ConstraintViolationBuilder
     */
    private $constraintViolationBuilder;

    protected function setUp(): void
    {
        $this->leadFieldRepository        = $this->createMock(LeadFieldRepository::class);
        $this->translator                 = $this->createMock(TranslatorInterface::class);
        $this->identifierFields           = $this->createMock(IdentifierFields::class);
        $this->indexHelper                = $this->createMock(IndexHelper::class);
        $this->executionContext           = $this->createMock(ExecutionContextInterface::class);
        $this->formInterface              = $this->createMock(FormInterface::class);
        $this->constraintViolationBuilder = $this->createMock(ConstraintViolationBuilder::class);
        $this->fieldType                  = new FieldType($this->leadFieldRepository, $this->translator, $this->identifierFields, $this->indexHelper);
    }

    public function testThatItFailsValidationIfTheDefaultValueExceedsTheFieldLengthLimit(): void
    {
        $this->executionContext->expects($this->once())
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $this->formInterface->expects($this->once())
            ->method('getViewData')
            ->willReturn($leadField);

        $limit = 100;
        $leadField->setCharLengthLimit($limit);

        $this->executionContext->expects($this->once(1))
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', $limit + 10);

        $this->constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);
    }

    public function testThatItPassesValidationIfTheFieldHasCorrectLength(): void
    {
        $this->executionContext->expects($this->exactly(2))
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $this->formInterface->expects($this->exactly(2))
            ->method('getViewData')
            ->willReturn($leadField);

        $limit = 100;
        $leadField->setCharLengthLimit($limit);

        $this->executionContext->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', $limit);

        $this->constraintViolationBuilder->expects($this->never())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);

        $value = str_repeat('a', $limit - 1);
        FieldType::validateDefaultValue($value, $this->executionContext);
    }

    public function testThatItDoesntValidateLengthForHtmlFields(): void
    {
        $this->executionContext->expects($this->once())
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $leadField->setType('html');
        $this->formInterface->expects($this->once())
            ->method('getViewData')
            ->willReturn($leadField);

        $this->executionContext->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', 1000000);

        $this->constraintViolationBuilder->expects($this->never())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);
    }

    public function testThatItDoesntValidateLengthForTextareaFields(): void
    {
        $this->executionContext->expects($this->once())
            ->method('getRoot')
            ->willReturn($this->formInterface);

        $leadField = new LeadField();
        $leadField->setType('textarea');
        $this->formInterface->expects($this->once())
            ->method('getViewData')
            ->willReturn($leadField);

        $this->executionContext->expects($this->never())
            ->method('buildViolation')
            ->willReturn($this->constraintViolationBuilder);

        $value = str_repeat('a', 1000000);

        $this->constraintViolationBuilder->expects($this->never())
            ->method('addViolation');

        FieldType::validateDefaultValue($value, $this->executionContext);
    }
}
