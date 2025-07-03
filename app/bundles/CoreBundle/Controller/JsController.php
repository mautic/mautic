<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

class JsController extends CommonController
{
    public function indexAction(
        #[Autowire(param: 'kernel.debug')]
        bool $kernelDebug,
    ): Response {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $dispatcher = $this->dispatcher;
        $event      = new BuildJsEvent($this->getJsHeader(), $kernelDebug);

        if ($dispatcher->hasListeners(CoreEvents::BUILD_MAUTIC_JS)) {
            $dispatcher->dispatch($event, CoreEvents::BUILD_MAUTIC_JS);
        }

        return new Response($event->getJs(), 200, ['Content-Type' => 'application/javascript']);
    }

    /**
     * Build a JS header for the Mautic embedded JS.
     */
    protected function getJsHeader(): string
    {
        $year = date('Y');

        return <<<JS
/**
 * @package     MauticJS
 * @copyright   {$year} Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
JS;
    }
}
