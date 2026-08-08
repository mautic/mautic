<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

final class JsController extends CommonController
{
    public function indexAction(
        #[Autowire(param: 'kernel.debug')]
        bool $kernelDebug,
    ): Response {
        return $this->buildJs($kernelDebug);
    }

    public function essentialAction(
        #[Autowire(param: 'kernel.debug')]
        bool $kernelDebug,
    ): Response {
        return $this->buildJs($kernelDebug, [BuildJsScope::RUNTIME, BuildJsScope::ESSENTIAL]);
    }

    public function trackingAction(
        #[Autowire(param: 'kernel.debug')]
        bool $kernelDebug,
    ): Response {
        return $this->buildJs($kernelDebug, [BuildJsScope::TRACKING]);
    }

    /**
     * @param BuildJsScope[] $acceptedScopes
     */
    private function buildJs(bool $kernelDebug, array $acceptedScopes = [
        BuildJsScope::RUNTIME,
        BuildJsScope::ESSENTIAL,
        BuildJsScope::TRACKING,
    ]): Response
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $event = new BuildJsEvent($this->getJsHeader(), $kernelDebug, $acceptedScopes);

        if ($this->dispatcher->hasListeners(CoreEvents::BUILD_MAUTIC_JS)) {
            $this->dispatcher->dispatch($event, CoreEvents::BUILD_MAUTIC_JS);
        }

        return new Response($event->getJs(), Response::HTTP_OK, ['Content-Type' => 'application/javascript']);
    }

    /**
     * Build a JS header for the Mautic embedded JS.
     */
    private function getJsHeader(): string
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
