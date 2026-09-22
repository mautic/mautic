<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Extension;

use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Security\OIDC\UserTypeExtension;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

final class UserTypeExtensionTest extends TestCase
{
    public function testGetExtendedTypes(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $userTypeExtension   = new UserTypeExtension($parameters, $subjectIdRepository);

        $this->assertEquals([UserType::class], $userTypeExtension::getExtendedTypes());
    }

    public function testBuildFormAddsSubjectIdWhenOpenIdIsEnabled(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $userTypeExtension   = new UserTypeExtension($parameters, $subjectIdRepository);
        $formBuilder         = $this->createMock(FormBuilderInterface::class);

        $formBuilder->expects($this->once())
            ->method('add');

        $formBuilder->expects($this->once())
            ->method('create')
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturn($formBuilder);

        $userTypeExtension->buildForm($formBuilder, []);
    }

    public function testBuildFormDoesNotAddSubjectIdWhenOpenIdIsDisabled(): void
    {
        $parameters          = (new ParametersBuilder())->withIsEnabled(false)->build();
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $userTypeExtension   = new UserTypeExtension($parameters, $subjectIdRepository);
        $formBuilder         = $this->createMock(FormBuilderInterface::class);

        $formBuilder->expects($this->never())
            ->method('add');

        $formBuilder->expects($this->never())
            ->method('create')
            ->willReturn($formBuilder);

        $formBuilder->expects($this->never())
            ->method('addModelTransformer')
            ->willReturn($formBuilder);

        $userTypeExtension->buildForm($formBuilder, []);
    }
}
