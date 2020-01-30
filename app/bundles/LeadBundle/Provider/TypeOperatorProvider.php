<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

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

    /**
     * {@inheritdoc}
     */
    public function getOperatorsIncluding(array $operators): array
    {
        return $this->getOperatorChoiceList(['include' => $operators]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorsExcluding(array $operators): array
    {
        return $this->getOperatorChoiceList(['exclude' => $operators]);
    }

    /**
     * {@inheritdoc}
     */
    public function getChoicesForField(string $fieldType, string $fieldAlias): array
    {
        $aliasChoices = $this->getAllChoicesForListFieldAliases();
        $typeChoices  = $this->getAllChoicesForListFieldTypes();

        if (isset($aliasChoices[$fieldAlias])) {
            return $aliasChoices[$fieldAlias];
        }

        if (isset($typeChoices[$fieldType])) {
            return $typeChoices[$fieldType];
        }

        throw new ChoicesNotFoundException("No choices for field type {$fieldType} nor alias {$fieldAlias} were found");
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorsForFieldType(string $fieldType): array
    {
        // This condition can be removed after strict types will be implemented.
        if (!is_string($fieldType)) {
            throw new \InvalidArgumentException('getOperatorsForFieldType param must be string. '.print_r($fieldType, true).' provided');
        }

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

    public function getAllChoicesForListFieldTypes(): array
    {
        $this->lookForFieldChoices();

        return $this->cachedTypeChoices;
    }

    public function getAllChoicesForListFieldAliases()
    {
        $this->lookForFieldChoices();

        return $this->cachedAliasChoices;
    }

    public function adjustFilterPropertiesType(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails): FormInterface
    {
        $event = new FilterPropertiesTypeEvent($form, $fieldAlias, $fieldObject, $operator, $fieldDetails);
        $this->dispatcher->dispatch(LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD, $event);

        return $event->getFilterPropertiesForm();
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

    private function lookForFieldChoices(): void
    {
        if (empty($this->cachedTypeChoices)) {
            $event = new ListFieldChoicesEvent();

            $this->dispatcher->dispatch(LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE, $event);

            $this->cachedTypeChoices  = $event->getChoicesForAllListFieldTypes();
            $this->cachedAliasChoices = $event->getChoicesForAllListFieldAliases();
        }
    }
}
