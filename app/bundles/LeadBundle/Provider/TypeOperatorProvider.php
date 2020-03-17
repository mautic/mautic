<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class TypeOperatorProvider implements TypeOperatorProviderInterface
{
    use OperatorListTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var FilterOperatorProviderInterface
     */
    private $filterOperatorProvider;

    /**
     * @var array
     */
    private $cachedTypeOperators = [];

    /**
     * @var array
     */
    private $cachedTypeOperatorsChoices = [];

    /**
     * @var array
     */
    private $cachedTypeChoices = [];

    /**
     * @var array
     */
    private $cachedAliasChoices = [];

    public function __construct(
        EventDispatcherInterface $dispatcher,
        FilterOperatorProviderInterface $filterOperatorProvider
    ) {
        $this->dispatcher             = $dispatcher;
        $this->filterOperatorProvider = $filterOperatorProvider;
    }

    public function getOperatorsIncluding(array $operators): array
    {
        return $this->getOperatorChoiceList(['include' => $operators]);
    }

    public function getOperatorsExcluding(array $operators): array
    {
        return $this->getOperatorChoiceList(['exclude' => $operators]);
    }

    public function getOperatorsForFieldType(string $fieldType): array
    {
        // If we already processed this
        if (isset($this->cachedTypeOperatorsChoices[$fieldType])) {
            return $this->cachedTypeOperatorsChoices[$fieldType];
        }

        $typeOperators = $this->getAllTypeOperators();

        if (array_key_exists($fieldType, $typeOperators)) {
            $this->cachedTypeOperatorsChoices[$fieldType] = $this->getOperatorChoiceList($typeOperators[$fieldType]);
        } else {
            $this->cachedTypeOperatorsChoices[$fieldType] = $this->getOperatorChoiceList($typeOperators['default']);
        }

        return $this->cachedTypeOperatorsChoices[$fieldType];
    }

    public function getAllTypeOperators(): array
    {
        if (empty($this->cachedTypeOperators)) {
            $event = new TypeOperatorsEvent();

            $this->dispatcher->dispatch(LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE, $event);

            $this->cachedTypeOperators = $event->getOperatorsForAllFieldTypes();
        }

        return $this->cachedTypeOperators;
    }

    /**
     * This method will add the default operators for the $type like the getOperatorsForFieldType() method
     * but also allows plugins to add more operators.
     */
    public function getOperatorsForField(string $type, string $field): array
    {
        $event = new FieldOperatorsEvent(
            $type,
            $field,
            $this->filterOperatorProvider->getAllOperators(),
            $this->getOperatorsForFieldType($type)
        );

        $this->dispatcher->dispatch(LeadEvents::COLLECT_OPERATORS_FOR_FIELD, $event);

        return $event->getOperators();
    }

    /**
     * Overwriting deprecated method from OperatorListTrait.
     *
     * @param string $operator
     *
     * @return array
     */
    public function getFilterExpressionFunctions($operator = null)
    {
        $operatorOption = $this->filterOperatorProvider->getAllOperators();

        return (null === $operator) ? $operatorOption : $operatorOption[$operator];
    }
}
