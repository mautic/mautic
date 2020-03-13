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

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class FilterPropertiesTypeEvent extends Event
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var string
     */
    private $fieldAlias;

    /**
     * @var string
     */
    private $fieldObject;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var array
     */
    private $fieldDetails;

    public function __construct(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails)
    {
        $this->form         = $form;
        $this->fieldAlias   = $fieldAlias;
        $this->fieldObject  = $fieldObject;
        $this->operator     = $operator;
        $this->fieldDetails = $fieldDetails;
    }

    public function getFilterPropertiesForm(): FormInterface
    {
        return $this->form;
    }

    public function getFieldAlias(): string
    {
        return $this->fieldAlias;
    }

    public function getFieldObject(): string
    {
        return $this->fieldObject;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param string ...$operators
     */
    public function operatorIsOneOf(string ...$operators): bool
    {
        return in_array($this->getOperator(), $operators);
    }

    /**
     * @param string ...$fieldTypes
     */
    public function fieldTypeIsOneOf(string ...$fieldTypes): bool
    {
        return in_array($this->getFieldType(), $fieldTypes);
    }

    public function getFieldType(): string
    {
        return $this->fieldDetails['properties']['type'];
    }

    public function getFieldDetails(): array
    {
        return $this->fieldDetails;
    }

    public function getFieldChoices(): array
    {
        return $this->fieldDetails['properties']['list'] ?? [];
    }

    public function filterShouldBeDisabled(): bool
    {
        return $this->operatorIsOneOf(OperatorOptions::EMPTY, OperatorOptions::NOT_EMPTY);
    }
}
