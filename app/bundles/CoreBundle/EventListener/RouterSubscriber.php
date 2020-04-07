<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RouterSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string|null
     */
    private $baseUrl;

    /**
     * @var string|null
     */
    private $scheme;

    /**
     * @var string|null
     */
    private $host;

    /**
     * @var string|null
     */
    private $httpsPort;

    /**
     * @var string|null
     */
    private $httpPort;

    /**
     * RouterSubscriber constructor.
     *
     * @param RouterInterface $router
     * @param null|string     $scheme
     * @param null|string     $host
     * @param null|string     $httpsPort
     * @param null|string     $httpPort
     * @param null|string     $baseUrl
     */
    public function __construct(RouterInterface $router, $scheme, $host, $httpsPort, $httpPort, $baseUrl)
    {
        $this->router    = $router;
        $this->scheme    = $scheme;
        $this->host      = $host;
        $this->httpsPort = $httpsPort;
        $this->httpPort  = $httpPort;
        $this->baseUrl   = $baseUrl;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['setRouterRequestContext', 1],
        ];
    }

    /**
     * This forces generated routes to be the same as what is configured as Mautic's site_url
     * in order to prevent mismatches between cached URLs generated during web requests and URLs generated
     * via CLI/cron jobs.
     *
     * @param GetResponseEvent $event
     */
    public function setRouterRequestContext(GetResponseEvent $event)
    {
        if (empty($this->host)) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        $originalContext = $this->router->getContext();
        if ($originalContext->getBaseUrl() && !$this->baseUrl) {
            // Likely in installation where the request parameters passed into this listener are not set yet so just use the original context
            return;
        }

        // Append index_dev.php for installations at the root level
        if ('dev' === MAUTIC_ENV && strpos($this->baseUrl, 'index_dev.php') === false) {
            $this->baseUrl = $this->baseUrl.'/index_dev.php';
        }

        $context = $this->router->getContext();
        $context->setBaseUrl($this->baseUrl);
        $context->setScheme($this->scheme);
        $context->setHost($this->host);
        $context->setHttpPort($this->httpPort);
        $context->setHttpsPort($this->httpsPort);
    }
}
