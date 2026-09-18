<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Type;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\Form\Type\SubjectIdType;
use Mautic\UserBundle\Security\OIDC\Service\LinkerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SubjectIdTypeTest extends TestCase
{
    public function testGetName(): void
    {
        $linker        = self::createMock(LinkerInterface::class);
        $translator    = self::createMock(TranslatorInterface::class);
        $subjectIdType = new SubjectIdType($linker, $translator);

        self::assertSame('user_openid', $subjectIdType->getName());
    }

    public function testConfigureOptions(): void
    {
        $linker        = self::createMock(LinkerInterface::class);
        $translator    = self::createMock(TranslatorInterface::class);
        $subjectIdType = new SubjectIdType($linker, $translator);

        $resolver = self::createMock(OptionsResolver::class);

        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'data_class'        => OidcSubjectId::class,
                'validation_groups' => [OidcSubjectId::class, 'subjectID'],
            ]);

        $subjectIdType->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $linker          = self::createMock(LinkerInterface::class);
        $translator      = self::createMock(TranslatorInterface::class);
        $eventDispatcher = new EventDispatcher();
        $formFactory     = self::createMock(FormFactoryInterface::class);
        $builder         = new FormBuilder('config', User::class, $eventDispatcher, $formFactory);

        $subjectIdType = new SubjectIdType($linker, $translator);
        $subjectIdType->buildForm($builder, []);

        self::assertTrue($builder->has('subjectID'));
        self::assertTrue($builder->getEventDispatcher()->hasListeners(FormEvents::POST_SUBMIT));
    }
}
