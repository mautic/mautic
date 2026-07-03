<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Mautic\LeadBundle\Validator\Constraints\UniqueCompanySegmentAlias;
use Mautic\LeadBundle\Validator\Constraints\UniqueCompanySegmentAliasValidator;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends \Symfony\Component\Validator\Test\ConstraintValidatorTestCase<\Mautic\LeadBundle\Validator\Constraints\UniqueCompanySegmentAliasValidator>
 */
class UniqueCompanySegmentAliasValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var MockObject&CompanySegmentRepository
     */
    private MockObject $segmentRepository;

    /**
     * @var MockObject&UserHelper
     */
    private MockObject $userHelper;

    public function testValueIsNotCompanySegment(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->assertNoActionsTaken();

        $this->validator->validate(new \stdClass(), $this->createMock(UniqueCompanySegmentAlias::class));
    }

    public function testConstraintIsNotUniqueAlias(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->assertNoActionsTaken();

        $this->validator->validate($this->createMock(CompanySegment::class), $this->createMock(Constraint::class));
    }

    public function testFieldOptionIsRequired(): void
    {
        $this->assertNoActionsTaken();

        $constraint        = $this->createMock(UniqueCompanySegmentAlias::class);
        $constraint->field = '';
        $this->validator->validate($this->createMock(CompanySegment::class), $constraint);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideNullOrEmpty')]
    public function testAliasIsNullOrEmpty(?string $value): void
    {
        $this->assertNoActionsTaken();

        $constraint        = $this->createMock(UniqueCompanySegmentAlias::class);
        $constraint->field = 'alias';
        $companySegment    = $this->createMock(CompanySegment::class);
        $companySegment->method('getAlias')
            ->willReturn($value);

        $this->validator->validate($companySegment, $constraint);
        $this->assertNoViolation();
    }

    public static function provideNullOrEmpty(): \Generator
    {
        yield 'alias is null' => [null];
        yield 'alias is empty' => [''];
    }

    public function testNoSegmentsFoundNoViolation(): void
    {
        $aliasName = 'alias';
        $segmentId = 38746584375698237;
        $user      = $this->createMock(User::class);

        $constraint        = $this->createMock(UniqueCompanySegmentAlias::class);
        $constraint->field = 'alias';
        $companySegment    = $this->createMock(CompanySegment::class);
        $companySegment->method('getAlias')
            ->willReturn($aliasName);
        $companySegment->method('getId')
            ->willReturn($segmentId);

        $this->segmentRepository->expects(self::once())
            ->method('getSegments')
            ->with($user, $aliasName, $segmentId)
            ->willReturn([]);
        $this->userHelper->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->validator->validate($companySegment, $constraint);
        $this->assertNoViolation();
    }

    public function testDuplicateSegmentViolation(): void
    {
        $aliasName  = 'alias';
        $aliasField = 'alias_field';
        $segmentId  = 38746584375698237;
        $user       = $this->createMock(User::class);

        $constraint        = $this->createMock(UniqueCompanySegmentAlias::class);
        $constraint->field = $aliasField;
        $companySegment    = $this->createMock(CompanySegment::class);
        $companySegment->method('getAlias')
            ->willReturn($aliasName);
        $companySegment->method('getId')
            ->willReturn($segmentId);

        $this->segmentRepository->expects(self::once())
            ->method('getSegments')
            ->with($user, $aliasName, $segmentId)
            ->willReturn([$this->createMock(CompanySegment::class)]);
        $this->userHelper->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->validator->validate($companySegment, $constraint);

        $this->buildViolation('This alias is already in use.')
            ->atPath('property.path.'.$aliasField)
            ->assertRaised();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->segmentRepository = $this->createMock(CompanySegmentRepository::class);
        $this->userHelper        = $this->createMock(UserHelper::class);

        return new UniqueCompanySegmentAliasValidator($this->segmentRepository, $this->userHelper);
    }

    private function assertNoActionsTaken(): void
    {
        $this->segmentRepository->expects(self::never())
            ->method('getSegments');
        $this->userHelper->expects(self::never())
            ->method('getUser');
    }
}
