<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Validator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Validator\PageHitValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PageHitValidatorTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelperMock;

    private MockObject&Constraint $constraintMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $this->constraintMock           = $this->createMock(Constraint::class);
    }

    public function testWhenPageHitValidatorIsDisabled(): void
    {
        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('validate_page_hit_required_data')
            ->willReturn(false);

        $pageHitValidator = new PageHitValidator($this->coreParametersHelperMock);

        $pageHitValidator->validate(null, $this->constraintMock);
    }

    public function testWhenHitObjectIsNull(): void
    {
        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('validate_page_hit_required_data')
            ->willReturn(true);

        $pageHitValidator = new PageHitValidator($this->coreParametersHelperMock);

        $this->expectException(UnexpectedTypeException::class);
        $pageHitValidator->validate(null, $this->constraintMock);
    }

    public function testWhenStatusCodeIs404(): void
    {
        $hitMock = $this->createMock(Hit::class);

        $hitMock->expects($this->once())
            ->method('getCode')
            ->willReturn(404);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('validate_page_hit_required_data')
            ->willReturn(true);

        $pageHitValidator = new PageHitValidator($this->coreParametersHelperMock);

        $pageHitValidator->validate($hitMock, $this->constraintMock);
    }

    public function testWhenPageIsNotNull(): void
    {
        $pageMock = $this->createMock(Page::class);
        $pageMock->setTitle('TestPage');

        $hitMock = $this->createMock(Hit::class);

        $hitMock->expects($this->once())
            ->method('getCode')
            ->willReturn(200);

        $hitMock->expects($this->once())
            ->method('getPage')
            ->willReturn($pageMock);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('validate_page_hit_required_data')
            ->willReturn(true);

        $pageHitValidator = new PageHitValidator($this->coreParametersHelperMock);

        $pageHitValidator->validate($hitMock, $this->constraintMock);
    }

    public function testWhenRequiredDataIsNull(): void
    {
        $hitMock = $this->createMock(Hit::class);

        $hitMock->expects($this->once())
            ->method('getCode')
            ->willReturn(200);

        $this->coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('validate_page_hit_required_data')
            ->willReturn(true);

        // mock the violation builder
        $builder = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->onlyMethods(['addViolation'])
            ->getMock();

        // mock the validator context
        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->onlyMethods(['buildViolation'])
            ->getMock();

        $builder->expects($this->once())
            ->method('addViolation');

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo('page_id / redirect_id / page_url & page_title should not be empty'))
            ->willReturn($builder);

        $pageHitValidator = new PageHitValidator($this->coreParametersHelperMock);

        $pageHitValidator->initialize($context);

        $pageHitValidator->validate($hitMock, $this->constraintMock);
    }
}
