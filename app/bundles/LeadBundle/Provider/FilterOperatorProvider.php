<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FilterOperatorProvider implements FilterOperatorProviderInterface
{
    /**
     * @var mixed[]
     */
    private array $cachedOperators = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getAllOperators(): array
    {
        if (empty($this->cachedOperators)) {
            $event = new LeadListFiltersOperatorsEvent([], $this->translator);

            $this->dispatcher->dispatch($event, LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE);

            $this->cachedOperators = $this->translateOperatorLabels($event->getOperators());
        }

        return $this->cachedOperators;
    }

    /**
     * @param mixed[] $operators
     *
     * @return mixed[]
     */
    private function translateOperatorLabels(array $operators): array
    {
        foreach ($operators as $key => $operatorSettings) {
            $operators[$key]['label'] = $this->translator->trans($operatorSettings['label']);
        }

        return $operators;
    }
}
