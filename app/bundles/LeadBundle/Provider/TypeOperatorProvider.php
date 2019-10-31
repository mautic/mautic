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
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
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
    private $cachedListChoices = [];

    /**
     * @param EventDispatcherInterface        $dispatcher
     * @param FilterOperatorProviderInterface $filterOperatorProvider
     */
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
    public function getOperatorsIncluding(array $operators)
    {
        $this->getOperatorChoiceList(['include' => $operators]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorsExcluding(array $operators)
    {
        $this->getOperatorChoiceList(['exclude' => $operators]);
    }

    /**
     * {@inheritdoc}
     */
    public function getChoicesForListFieldType($fieldType)
    {
        // This condition can be removed after strict types will be implemented.
        if (!is_string($fieldType)) {
            throw new \InvalidArgumentException('getOperatorsForFieldType param must be string. '.print_r($fieldType, true).' provided');
        }

        $typeChoices = $this->getAllChoicesForListFieldTypes();

        // If we already processed this
        if (isset($typeChoices[$fieldType])) {
            return $typeChoices[$fieldType];
        }

        throw new ChoicesNotFoundException("No choices for field type {$fieldType} were found");
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorsForFieldType($fieldType)
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

            return $this->cachedTypeOperatorsChoices[$fieldType];
        }

        throw new OperatorsNotFoundException("No filer operators for field type {$fieldType} were found");
    }

    /**
     * {@inheritdoc}
     */
    public function getAllTypeOperators()
    {
        if (empty($this->cachedTypeOperators)) {
            $event = new TypeOperatorsEvent();

            $this->dispatcher->dispatch(LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE, $event);

            $this->cachedTypeOperators = $event->getOperatorsForAllFieldTypes();
        }

        return $this->cachedTypeOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllChoicesForListFieldTypes()
    {
        if (empty($this->cachedListChoices)) {
            $event = new ListFieldChoicesEvent();

            $this->dispatcher->dispatch(LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE, $event);

            $this->cachedListChoices = $event->getChoicesForAllListFieldTypes();
        }

        return $this->cachedListChoices;
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
