<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FieldChoicesProvider implements FieldChoicesProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $cachedTypeChoices = [];

    /**
     * @var array
     */
    private $cachedAliasChoices = [];

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

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

    private function getAllChoicesForListFieldTypes(): array
    {
        $this->lookForFieldChoices();

        return $this->cachedTypeChoices;
    }

    private function getAllChoicesForListFieldAliases()
    {
        $this->lookForFieldChoices();

        return $this->cachedAliasChoices;
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
