<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserTypeTest extends TestCase
{
    private UserType $type;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $model = $this->createMock(UserModel::class);
        $model->method('getLookupResults')->willReturn([]);

        $languageHelper = $this->createMock(LanguageHelper::class);
        $languageHelper->method('fetchLanguages')->willReturn([]);
        $languageHelper->method('getSupportedLanguages')->willReturn(['en_US' => 'English']);

        $this->type = new UserType($translator, $model, $languageHelper);
    }

    public function testPasswordFieldsNeverExposeAValueAndDisableBrowserAutofill(): void
    {
        $capturedOptions = null;

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnCallback(
            function (string $name, ?string $type = null, array $options = []) use (&$capturedOptions, $builder): FormBuilderInterface {
                if ('plainPassword' === $name) {
                    $capturedOptions = $options;
                }

                return $builder;
            }
        );

        $this->type->buildForm($builder, ['data' => new User(), 'in_profile' => true]);

        $this->assertIsArray($capturedOptions, 'The plainPassword field was not built.');

        foreach (['first_options', 'second_options'] as $repeatedOptionsKey) {
            $attr = $capturedOptions[$repeatedOptionsKey]['attr'];

            // Symfony's PasswordType never echoes the current password back into the
            // "value" attribute as long as always_empty stays at its default (true) -
            // nothing in this attr map may override that.
            $this->assertArrayNotHasKey('value', $attr);
            $this->assertArrayNotHasKey('always_empty', $capturedOptions[$repeatedOptionsKey]);

            // The browser's built-in password manager must not offer to autofill these
            // fields, and known third-party password manager extensions must be told
            // to ignore them too.
            $this->assertSame('new-password', $attr['autocomplete']);
            $this->assertSame('true', $attr['data-lpignore']);
            $this->assertSame('true', $attr['data-1p-ignore']);
            $this->assertSame('true', $attr['data-bwignore']);
            $this->assertSame('other', $attr['data-form-type']);
        }
    }
}
