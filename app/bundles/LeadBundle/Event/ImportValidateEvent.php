<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Form;

class ImportValidateEvent extends Event
{
    private string $routeObjectName;
    private Form $form;
    private ?int $ownerId = null;
    private ?int $list    = null;

    /**
     * @var mixed[]
     */
    private array $matchedFields = [];

    /**
     * @var mixed[]
     */
    private array $tags = [];

    public function __construct(string $routeObjectName, Form $form)
    {
        $this->routeObjectName = $routeObjectName;
        $this->form            = $form;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    /**
     * Check if the form we're validating has errors.
     */
    public function hasErrors(): bool
    {
        return (bool) count($this->form->getErrors());
    }

    /**
     * Check if the import is for said route object and notes if the object exist.
     */
    public function importIsForRouteObject(string $routeObject): bool
    {
        return $this->getRouteObjectName() === $routeObject;
    }

    public function getRouteObjectName(): string
    {
        return $this->routeObjectName;
    }

    /**
     * Set the matchedFields in the event.
     *
     * @param mixed[] $matchedFields
     */
    public function setMatchedFields(array $matchedFields): void
    {
        $this->matchedFields = $matchedFields;
    }

    /**
     * @return mixed[]
     */
    public function getMatchedFields(): array
    {
        return $this->matchedFields;
    }

    public function setOwnerId(?int $ownerId = null): void
    {
        $this->ownerId = $ownerId;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function setList(?int $list = null): void
    {
        $this->list = $list;
    }

    public function getList(): ?int
    {
        return $this->list;
    }

    /**
     * @param mixed[] $tags
     */
    public function setTags(array $tags = []): void
    {
        $this->tags = $tags;
    }

    /**
     * @return mixed[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
