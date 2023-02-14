<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Form\Extension;

use Mautic\OpenIdBundle\Form\Extension\UserTypeExtension;
use Mautic\OpenIdBundle\Repository\SubjectIdRepository;
use Mautic\OpenIdBundle\Tests\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Form\Type\UserType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class UserTypeExtensionTest extends TestCase
{
    public function testGetExtendedTypes(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $userTypeExtension   = new UserTypeExtension($parameters, $subjectIdRepository);

        self::assertEquals([UserType::class], $userTypeExtension::getExtendedTypes());
    }

    public function testBuildFormAddsSubjectIdWhenOpenIdIsEnabled(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $userTypeExtension   = new UserTypeExtension($parameters, $subjectIdRepository);
        $formBuilder         = self::createMock(FormBuilderInterface::class);

        $formBuilder->expects(self::once())
            ->method('add');

        $formBuilder->expects(self::once())
            ->method('create')
            ->willReturn($formBuilder);

        $formBuilder->expects(self::once())
            ->method('addModelTransformer')
            ->willReturn($formBuilder);

        $userTypeExtension->buildForm($formBuilder, []);
    }

    public function testBuildFormDoesNotAddSubjectIdWhenOpenIdIsDisabled(): void
    {
        $parameters          = (new ParametersBuilder())->withIsEnabled(false)->build();
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $userTypeExtension   = new UserTypeExtension($parameters, $subjectIdRepository);
        $formBuilder         = self::createMock(FormBuilderInterface::class);

        $formBuilder->expects(self::never())
            ->method('add');

        $formBuilder->expects(self::never())
            ->method('create')
            ->willReturn($formBuilder);

        $formBuilder->expects(self::never())
            ->method('addModelTransformer')
            ->willReturn($formBuilder);

        $userTypeExtension->buildForm($formBuilder, []);
    }
}
