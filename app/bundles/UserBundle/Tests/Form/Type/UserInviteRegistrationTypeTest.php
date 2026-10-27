<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserInviteRegistrationType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserInviteRegistrationTypeTest extends TestCase
{
    private MockObject&LanguageHelper $languageHelper;

    private UserInviteRegistrationType $type;

    protected function setUp(): void
    {
        $this->languageHelper = $this->createMock(LanguageHelper::class);
        $this->type           = new UserInviteRegistrationType($this->languageHelper);
    }

    public function testBuildFormAddsInviteRegistrationFields(): void
    {
        $this->languageHelper->expects($this->once())
            ->method('fetchLanguages')
            ->with(false, false)
            ->willReturn([
                'fr_FR' => ['name' => 'French'],
                'de_DE' => ['name' => 'German'],
            ]);

        $this->languageHelper->expects($this->once())
            ->method('getSupportedLanguages')
            ->willReturn([
                'en_US' => 'English',
                'es_ES' => 'Spanish',
            ]);

        $fieldNames = [];
        $builder    = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(6))
            ->method('add')
            ->willReturnCallback(function (string $name) use (&$fieldNames, $builder): FormBuilderInterface {
                $fieldNames[] = $name;

                return $builder;
            });

        $this->type->buildForm($builder, []);

        $this->assertSame(
            ['username', 'firstName', 'lastName', 'plainPassword', 'locale', 'buttons'],
            $fieldNames
        );
    }

    public function testConfigureOptionsSetsInviteRegistrationDefaults(): void
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        $this->assertSame(
            [
                'data_class'         => User::class,
                'validation_groups'  => [
                    User::class,
                    'determineValidationGroups',
                ],
                'ignore_formexit'    => true,
                'allow_extra_fields' => true,
            ],
            $resolver->resolve()
        );
    }
}
