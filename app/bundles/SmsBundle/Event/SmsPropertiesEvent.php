<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Event;

use Symfony\Component\Form\FormBuilder;
use Symfony\Contracts\EventDispatcher\Event;

class SmsPropertiesEvent extends Event
{
    /**
     * @return array<mixed>
     */
    private array $fields = [];

    /**
     * @param array<mixed> $data
     */
    public function __construct(private FormBuilder $formBuilder, private array $data)
    {
    }

    public function getFormBuilder(): FormBuilder
    {
        return $this->formBuilder;
    }

    /**
     * @return array<mixed>
     */
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

    /**
     * @return array<mixed>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
