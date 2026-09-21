<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Extension;

use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Security\OIDC\Form\Extension\UserTypeExtension;
use Mautic\UserBundle\Security\OIDC\Repository\SubjectIdRepository;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
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
