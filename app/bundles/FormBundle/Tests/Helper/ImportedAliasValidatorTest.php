<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Exception\InvalidImportAliasException;
use Mautic\FormBundle\Helper\ImportedAliasValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ImportedAliasValidatorTest extends TestCase
{
    private ImportedAliasValidator $validator;

    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQL80Platform());

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $key, array $parameters): string => $key.': '.$parameters['%alias%']
        );

        $this->validator = new ImportedAliasValidator($connection, $translator);
    }

    #[DataProvider('validFieldAliasProvider')]
    public function testFieldAliasLikeTheGeneratedOnesIsValid(string $alias): void
    {
        $this->assertSame([], $this->validator->getErrors([Field::ENTITY_NAME => [['alias' => $alias]]]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validFieldAliasProvider(): iterable
    {
        yield 'label'                    => ['email'];
        yield 'with an underscore'       => ['first_name'];
        yield 'with a number added'      => ['email2'];
        yield 'reserved word prefixed'   => ['f_select'];
        yield 'number prefixed'          => ['f_123'];
        yield 'starting with a number'   => ['1st_choice'];
        yield 'longest MySQL name'       => [str_repeat('a', 64)];
    }

    #[DataProvider('invalidFieldAliasProvider')]
    public function testFieldAliasThatIsNotAUsableColumnNameIsRejected(mixed $alias, string $expected): void
    {
        $this->assertSame(
            ['mautic.form.import.invalid_field_alias: '.$expected],
            $this->validator->getErrors([Field::ENTITY_NAME => [['alias' => 'email'], ['alias' => $alias]]])
        );
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidFieldAliasProvider(): iterable
    {
        yield 'more SQL'          => ['review_field LONGTEXT, ADD review_extra', 'review_field LONGTEXT, ADD review_extra'];
        yield 'quote'             => ['a` TEXT, ADD `b', 'a` TEXT, ADD `b'];
        yield 'dot'               => ['other.column', 'other.column'];
        yield 'dash'              => ['e-mail', 'e-mail'];
        yield 'uppercase'         => ['Email', 'Email'];
        yield 'new line'          => ["email\n", "email\n"];
        yield 'only a number'     => ['123', '123'];
        yield 'reserved word'     => ['select', 'select'];
        yield 'too long'          => [str_repeat('a', 65), str_repeat('a', 65)];
        yield 'empty'             => ['', ''];
        yield 'missing'           => [null, 'null'];
        yield 'not a string'      => [['email'], 'array'];
    }

    public function testFieldWithoutAliasIsRejected(): void
    {
        $this->assertSame(
            ['mautic.form.import.invalid_field_alias: null'],
            $this->validator->getErrors([Field::ENTITY_NAME => [['label' => 'Email']]])
        );
    }

    public function testLongAliasIsShortenedInTheMessage(): void
    {
        $errors = $this->validator->getErrors([Field::ENTITY_NAME => [['alias' => str_repeat('a', 100)]]]);

        $this->assertSame(['mautic.form.import.invalid_field_alias: '.str_repeat('a', 70).'…'], $errors);
    }

    #[DataProvider('validFormAliasProvider')]
    public function testFormAliasThatFitsTheTableNameIsValid(?string $alias): void
    {
        $this->assertSame([], $this->validator->getErrors([Form::ENTITY_NAME => [['alias' => $alias]]]));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function validFormAliasProvider(): iterable
    {
        yield 'generated'                     => ['contactus'];
        yield 'longer than the generated one' => ['formactions'];
        // These get one generated when the form is saved
        yield 'empty'                         => [''];
        yield 'missing'                       => [null];
    }

    public function testFormAliasThatChangesTheTableNameIsRejected(): void
    {
        $this->assertSame(
            ['mautic.form.import.invalid_form_alias: x (id INT); DROP TABLE leads; --'],
            $this->validator->getErrors([Form::ENTITY_NAME => [['alias' => 'x (id INT); DROP TABLE leads; --']]])
        );
    }

    public function testValidateThrowsWithEveryMessage(): void
    {
        $this->expectException(InvalidImportAliasException::class);
        $this->expectExceptionMessage('mautic.form.import.invalid_form_alias: a-b mautic.form.import.invalid_field_alias: c d');

        $this->validator->validate([
            Form::ENTITY_NAME  => [['alias' => 'a-b']],
            Field::ENTITY_NAME => [['alias' => 'c d']],
        ]);
    }

    public function testValidateAcceptsAnImportWithoutForms(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate(['campaign' => [['name' => 'Campaign']]]);
    }
}
