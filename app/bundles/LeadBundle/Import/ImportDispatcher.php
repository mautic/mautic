<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Import;

use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Event\ImportBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ImportDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ImportDispatcher constructor.
     *
     * @param RequestStack             $requestStack
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(RequestStack $requestStack, EventDispatcherInterface $dispatcher)
    {
        $this->request    = $requestStack->getCurrentRequest();
        $this->dispatcher = $dispatcher;
    }

    public function dispatchBuilder(Import $import = null)
    {
        $importBuilderEvent = new ImportBuilderEvent($this->request, $import);
        $this->dispatcher->dispatch(LeadEvents::IMPORT_BUILDER, $importBuilderEvent);

        return $importBuilderEvent;
    }
}
