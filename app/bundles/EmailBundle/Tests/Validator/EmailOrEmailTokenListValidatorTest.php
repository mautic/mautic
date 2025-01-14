<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Validator;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Validator\EmailOrEmailTokenList;
use Mautic\EmailBundle\Validator\EmailOrEmailTokenListValidator;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Validator\CustomFieldValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Context\ExecutionContext;

final class EmailOrEmailTokenListValidatorTest extends TestCase
{
    /**
     * @dataProvider provider
     *
     * @param mixed $value
     */
    public function testNoEmailsProvided($value, int $expectedViolationCount, callable $getFieldMocker, callable $violationResult): void
    {
        $context = new class() extends ExecutionContext {
            /** @var callable */
            public $violationResult;
            public int $violationCount = 0;

            public function __construct()
            {
            }

            /**
             * @param mixed[] $parameters
             *
             * @return void
             */
            public function addViolation($message, array $parameters = [])
            {
                ++$this->violationCount;
                ($this->violationResult)($message, $parameters);
            }
        };

        $context->violationResult = $violationResult;

        $translator = new class() extends Translator {
            public function __construct()
            {
            }

            /**
             * @param mixed[] $parameters
             */
            public function trans($id, array $parameters = [], $domain = null, $locale = null)
            {
                return $id;
            }
        };

        $dispatcher = new class() extends EventDispatcher {
            public function __construct()
            {
            }

            public function dispatch($eventName, ?Event $event = null)
            {
                return $event;
            }
        };

        $fieldModel = new class() extends FieldModel {
            /** @var callable */
            public $getFieldMocker;

            public function __construct()
            {
            }

            public function getEntityByAlias($alias, $categoryAlias = null, $lang = null)
            {
                return ($this->getFieldMocker)($alias);
            }
        };

        $fieldModel->getFieldMocker = $getFieldMocker;

        $emaiOrEmailTokenListValidator = new EmailOrEmailTokenListValidator(
            new EmailValidator($translator, $dispatcher),
            new CustomFieldValidator($fieldModel, $translator)
        );

        $emaiOrEmailTokenListValidator->initialize($context);
        $emaiOrEmailTokenListValidator->validate($value, new EmailOrEmailTokenList());

        Assert::assertSame($expectedViolationCount, $context->violationCount);
    }

    /**
     * @return \Generator<mixed[]>
     */
    public function provider(): \Generator
    {
        // Test null value.
        yield [
            null,
            0,
            function () {
                $this->fail('Field should not be fetched');
            },
            function () {
                $this->fail('Null value should not be validated.');
            },
        ];

        // Test empty value.
        yield [
            '',
            0,
            function () {
                $this->fail('Field should not be fetched');
            },
            function () {
                $this->fail('Empty string value should not be validated.');
            },
        ];

        // Test invalid email and invalid token.
        yield [
            'somestring',
            1,
            function () {
                $this->fail('Field should not be fetched');
            },
            function ($message, array $parameters = []) {
                Assert::assertSame('mautic.email.email_or_token.not_valid', $message);
                Assert::assertSame(
                    [
                        '%value%'   => 'somestring',
                        '%details%' => '\'somestring\' is not a valid contact field token. A valid token example: \'{contactfield=firstname|John}\'',
                    ],
                    $parameters
                );
            },
        ];

        // Test that valid email address do not add any violation.
        yield [
            'john@doe.com',
            0,
            function () {
                $this->fail('Field should not be fetched');
            },
            function () {
                $this->fail('Valid email address value should not add violation.');
            },
        ];

        // Test valid email address with invalid token.
        yield [
            'john@doe.com, somestring',
            1,
            function () {
                $this->fail('Field should not be fetched');
            },
            function ($message, array $parameters = []) {
                Assert::assertSame('mautic.email.email_or_token.not_valid', $message);
                Assert::assertSame(
                    [
                        '%value%'   => 'somestring',
                        '%details%' => '\'somestring\' is not a valid contact field token. A valid token example: \'{contactfield=firstname|John}\'',
                    ],
                    $parameters
                );
            },
        ];

        yield [
            'john@doe.com, {contactfield=somefield | invalid-default-email-address}',
            1,
            function () {
                $this->fail('Field should not be fetched');
            },
            function ($message, array $parameters = []) {
                Assert::assertSame('mautic.email.email_or_token.not_valid', $message);
                Assert::assertSame(
                    [
                        '%value%'   => '{contactfield=somefield | invalid-default-email-address}',
                        '%details%' => 'mautic.email.address.invalid_format',
                    ],
                    $parameters
                );
            },
        ];

        // Test error when the field is not found in the database.
        yield [
            'john@doe.com, {contactfield=somefield|jane@doe.com}',
            1,
            function (string $alias) {
                Assert::assertSame('somefield', $alias);

                return null;
            },
            function ($message, array $parameters = []) {
                Assert::assertSame('mautic.email.email_or_token.not_valid', $message);
                Assert::assertSame(
                    [
                        '%value%'   => '{contactfield=somefield|jane@doe.com}',
                        '%details%' => 'mautic.lead.contact.field.not.found',
                    ],
                    $parameters
                );
            },
        ];

        // Test error when the field is found but is not type of email.
        yield [
            'john@doe.com, {contactfield=somefield}',
            1,
            function (string $alias) {
                Assert::assertSame('somefield', $alias);

                $field = new LeadField();
                $field->setAlias($alias);
                $field->setType('unicorn');

                return $field;
            },
            function ($message, array $parameters = []) {
                Assert::assertSame('mautic.email.email_or_token.not_valid', $message);
                Assert::assertSame(
                    [
                        '%value%'   => '{contactfield=somefield}',
                        '%details%' => 'mautic.lead.contact.wrong.field.type',
                    ],
                    $parameters
                );
            },
        ];

        // Test valid email addresses and valid tokens.
        yield [
            'john@doe.com, {contactfield=somefield|jane@doe.com}, jone@doe.email, {contactfield=somefield}',
            0,
            function (string $alias) {
                Assert::assertSame('somefield', $alias);

                $field = new LeadField();
                $field->setAlias($alias);
                $field->setType('email');

                return $field;
            },
            function () {
                $this->fail('There is no violation');
            },
        ];

        // Test valid email addresses and valid token but without a comma between.
        yield [
            'jone@doe.email {contactfield=somefield}',
            1,
            function (string $alias) {
                Assert::assertSame('somefield', $alias);

                $field = new LeadField();
                $field->setAlias($alias);
                $field->setType('email');

                return $field;
            },
            function ($message, array $parameters = []) {
                Assert::assertSame('mautic.email.email_or_token.not_valid', $message);
                Assert::assertSame(
                    [
                        '%value%'   => 'jone@doe.email {contactfield=somefield}',
                        '%details%' => '\'jone@doe.email {contactfield=somefield}\' is not a valid contact field token. A valid token example: \'{contactfield=firstname|John}\'',
                    ],
                    $parameters
                );
            },
        ];
    }
}
