<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FieldChoicesProvider implements FieldChoicesProviderInterface
{
    /**
     * @var mixed[]
     */
    private array $cachedTypeChoices = [];

    /**
     * @var mixed[]
     */
    private array $cachedAliasChoices = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getChoicesForField(string $fieldType, string $fieldAlias, string $search = ''): array
    {
        $aliasChoices = $this->getAllChoicesForListFieldAliases($search);
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
     * @return mixed[]
     */
    private function getAllChoicesForListFieldTypes(): array
    {
        $this->lookForFieldChoices();

        return $this->cachedTypeChoices;
    }

    /**
     * @return mixed[]
     */
    private function getAllChoicesForListFieldAliases(string $search = ''): array
    {
        $this->lookForFieldChoices($search);

        return $this->cachedAliasChoices;
    }

    private function lookForFieldChoices(string $search = ''): void
    {
        if (empty($this->cachedTypeChoices)) {
            $event = new ListFieldChoicesEvent();
            $event->setSearchTerm($search);
            $this->dispatcher->dispatch($event, LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE);

            $this->cachedTypeChoices  = $event->getChoicesForAllListFieldTypes();
            $this->cachedAliasChoices = $event->getChoicesForAllListFieldAliases();
        }
    }
}
