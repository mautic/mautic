<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Event;

use Symfony\Component\Form\FormBuilder;

class SmsPropertiesEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    private array $fields = [];

    public function __construct(private FormBuilder $formBuilder, private array $data)
    {
    }

    public function getFormBuilder(): FormBuilder
    {
        return $this->formBuilder;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function addField(string $child, string $type = null, array $options = []): void
    {
        $this->fields[] = [
            'child'   => $child,
            'type'    => $type,
            'options' => $options,
        ];
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
