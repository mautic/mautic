<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

final class FormAdjustmentEvent extends Event
{
    /**
     * @var FormInterface<FormInterface>
     */
    private FormInterface $form;

    private string $fieldAlias;

    private string $fieldObject;

    private string $operator;

    /**
     * @var mixed[]
     */
    private array $fieldDetails;

    /**
     * @param FormInterface<FormInterface> $form
     * @param mixed[]                      $fieldDetails
     */
    public function __construct(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails)
    {
        $this->form         = $form;
        $this->fieldAlias   = $fieldAlias;
        $this->fieldObject  = $fieldObject;
        $this->operator     = $operator;
        $this->fieldDetails = $fieldDetails;
    }

    /**
     * @return FormInterface<FormInterface>
     */
    public function getForm(): FormInterface
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

    /**
     * @return mixed[]
     */
    public function getFieldDetails(): array
    {
        return $this->fieldDetails;
    }

    /**
     * @return mixed[]
     */
    public function getFieldChoices(): array
    {
        return $this->fieldDetails['properties']['list'] ?? [];
    }

    public function filterShouldBeDisabled(): bool
    {
        return $this->operatorIsOneOf(OperatorOptions::EMPTY, OperatorOptions::NOT_EMPTY);
    }
}
