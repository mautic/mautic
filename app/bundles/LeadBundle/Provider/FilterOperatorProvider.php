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

use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class FilterOperatorProvider implements FilterOperatorProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $cachedOperators = [];

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ) {
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    public function getAllOperators(): array
    {
        if (empty($this->cachedOperators)) {
            $event = new LeadListFiltersOperatorsEvent([], $this->translator);

            $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE, $event);

            $this->cachedOperators = $this->translateOperatorLabels($event->getOperators());
        }

        return $this->cachedOperators;
    }

    private function translateOperatorLabels(array $operators): array
    {
        foreach ($operators as $key => $operatorSettings) {
            $operators[$key]['label'] = $this->translator->trans($operatorSettings['label']);
        }

        return $operators;
    }
}
