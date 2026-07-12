<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Form;

use Mautic\CoreBundle\Form\ToBcBccFieldsTrait;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Validator\MultipleEmailsValidValidator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ToBcBccFieldsTraitTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $multipleEmailsValidator = new MultipleEmailsValidValidator(new EmailValidator($translator, new EventDispatcher()));
        $validator               = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                MultipleEmailsValidValidator::class => $multipleEmailsValidator,
            ]))
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSingleValidEmailPasses(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => 'user@example.com', 'cc' => '', 'bcc' => '']);

        self::assertTrue($form->isValid());
    }

    public function testCommaSeparatedValidEmailsPasses(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => 'user1@example.com,user2@example.com', 'cc' => '', 'bcc' => '']);

        self::assertTrue($form->isValid());
    }

    public function testCommaSeparatedEmailsWithSpacesPasses(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => 'user1@example.com, user2@example.com', 'cc' => '', 'bcc' => '']);

        self::assertTrue($form->isValid());
    }

    public function testEmptyValuePasses(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => '', 'cc' => '', 'bcc' => '']);

        self::assertTrue($form->isValid());
    }

    public function testInvalidEmailFails(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => 'notanemail', 'cc' => '', 'bcc' => '']);

        self::assertFalse($form->isValid());
    }

    public function testZeroValueFails(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => '0', 'cc' => '', 'bcc' => '']);

        self::assertFalse($form->isValid());
    }

    public function testOneInvalidEmailInListFails(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => 'valid@example.com,notanemail', 'cc' => '', 'bcc' => '']);

        self::assertFalse($form->isValid());
    }

    public function testCcAndBccFieldsAlsoValidate(): void
    {
        $form = $this->factory->create(ToBcBccStubFormType::class);
        $form->submit(['to' => '', 'cc' => 'invalid', 'bcc' => 'also-invalid']);

        self::assertFalse($form->isValid());
    }
}

/**
 * @extends AbstractType<mixed>
 */
class ToBcBccStubFormType extends AbstractType
{
    use ToBcBccFieldsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addToBcBccFields($builder);
    }
}
