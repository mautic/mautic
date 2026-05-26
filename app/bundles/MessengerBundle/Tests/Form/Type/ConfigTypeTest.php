<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Tests\Form\Type;

use Mautic\ConfigBundle\Form\DataTransformer\DsnTransformerFactory;
use Mautic\ConfigBundle\Form\Type\DsnType;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\StandAloneButtonType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\MessengerBundle\Form\Type\ConfigType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigTypeTest extends TypeTestCase
{
    /**
     * @var TranslatorInterface&MockObject
     */
    private TranslatorInterface $translator;

    /**
     * @var DsnTransformerFactory&MockObject
     */
    private DsnTransformerFactory $dsnTransformerFactory;

    /**
     * @var CoreParametersHelper&MockObject
     */
    private CoreParametersHelper $coreParametersHelper;

    protected function setUp(): void
    {
        $this->translator            = $this->createMock(TranslatorInterface::class);
        $this->dsnTransformerFactory = $this->createMock(DsnTransformerFactory::class);
        $this->coreParametersHelper  = $this->createMock(CoreParametersHelper::class);

        parent::setUp();
    }

    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([
                new ConfigType($this->translator),
                new DsnType($this->dsnTransformerFactory, $this->coreParametersHelper),
                new SortableListType(),
                new StandAloneButtonType(),
            ], []),
        ];
    }

    /**
     * Regression for https://github.com/mautic/mautic/issues/16017.
     *
     * Symfony's MultiplierRetryStrategy throws an InvalidArgumentException at
     * runtime for any multiplier < 1, which previously crashed the queue
     * worker after the Queue Settings form was saved with `0`. The form must
     * reject sub-1 multipliers via a `GreaterThanOrEqual(1)` constraint.
     */
    public function testMultiplierFieldRejectsValuesBelowOne(): void
    {
        $constraints = $this->getFieldConstraints('messenger_retry_strategy_multiplier');

        $greaterThan = $this->findGreaterThanOrEqualConstraint($constraints);
        self::assertNotNull($greaterThan, 'multiplier field must declare a GreaterThanOrEqual constraint');
        self::assertSame(1, $greaterThan->value, 'multiplier minimum must be 1 to match Symfony\'s MultiplierRetryStrategy');
    }

    #[DataProvider('nonNegativeNumericFieldProvider')]
    public function testNumericRetryStrategyFieldsRejectNegativeValues(string $field): void
    {
        $constraints = $this->getFieldConstraints($field);

        $greaterThan = $this->findGreaterThanOrEqualConstraint($constraints);
        self::assertNotNull($greaterThan, sprintf('%s must declare a GreaterThanOrEqual constraint', $field));
        self::assertSame(0, $greaterThan->value, sprintf('%s minimum must be 0', $field));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonNegativeNumericFieldProvider(): iterable
    {
        yield 'max_retries' => ['messenger_retry_strategy_max_retries'];
        yield 'delay'       => ['messenger_retry_strategy_delay'];
        yield 'max_delay'   => ['messenger_retry_strategy_max_delay'];
    }

    /**
     * @return list<\Symfony\Component\Validator\Constraint>
     */
    private function getFieldConstraints(string $fieldName): array
    {
        $form = $this->factory->create(ConfigType::class);
        self::assertInstanceOf(FormInterface::class, $form);

        $field = $form->get($fieldName);
        self::assertInstanceOf(FormInterface::class, $field);

        return $field->getConfig()->getOption('constraints') ?? [];
    }

    /**
     * @param list<\Symfony\Component\Validator\Constraint> $constraints
     */
    private function findGreaterThanOrEqualConstraint(array $constraints): ?GreaterThanOrEqual
    {
        foreach ($constraints as $c) {
            if ($c instanceof GreaterThanOrEqual) {
                return $c;
            }
        }

        return null;
    }
}
