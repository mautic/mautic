<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Type;

use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\OidcSubjectIdType;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SubjectIdTypeTest extends TestCase
{
    public function testGetBlockPrefix(): void
    {
        $linker        = $this->createStub(LinkerInterface::class);
        $translator    = $this->createStub(TranslatorInterface::class);
        $subjectIdType = new OidcSubjectIdType($linker, $translator);

        $this->assertSame('user_openid', $subjectIdType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $linker        = $this->createStub(LinkerInterface::class);
        $translator    = $this->createStub(TranslatorInterface::class);
        $subjectIdType = new OidcSubjectIdType($linker, $translator);

        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'        => OidcSubjectId::class,
                'validation_groups' => [OidcSubjectId::class, 'subjectID'],
            ]);

        $subjectIdType->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $linker          = $this->createStub(LinkerInterface::class);
        $translator      = $this->createStub(TranslatorInterface::class);
        $eventDispatcher = new EventDispatcher();
        $formFactory     = $this->createStub(FormFactoryInterface::class);
        $builder         = new FormBuilder('config', User::class, $eventDispatcher, $formFactory);

        $subjectIdType = new OidcSubjectIdType($linker, $translator);
        $subjectIdType->buildForm($builder, []);

        $this->assertTrue($builder->has('subjectID'));
        $this->assertTrue($builder->getEventDispatcher()->hasListeners(FormEvents::POST_SUBMIT));
    }
}
