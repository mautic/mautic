<?php

namespace Mautic\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RouterSubscriber implements EventSubscriberInterface
{
    private string|int $httpsPort;

    private string|int $httpPort;

    /**
     * @param string|null $scheme
     * @param string|null $host
     * @param string|null $httpsPort
     * @param string|null $httpPort
     * @param string|null $baseUrl
     */
    public function __construct(
        private RouterInterface $router,
        private $scheme,
        private $host,
        $httpsPort,
        $httpPort,
        private $baseUrl,
    ) {
        $this->httpsPort = $httpsPort ?? 443;
        $this->httpPort  = $httpPort ?? 80;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['setRouterRequestContext', 1],
        ];
    }

    /**
     * This forces generated routes to be the same as what is configured as Mautic's site_url
     * in order to prevent mismatches between cached URLs generated during web requests and URLs generated
     * via CLI/cron jobs.
     */
    public function setRouterRequestContext(RequestEvent $event): void
    {
        if (empty($this->host)) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $originalContext = $this->router->getContext();

        // Remove index.php, and ending forward slash from the URL to match what is configured in SiteUrlEnvVars
        $originalBaseUrl = str_replace(['index.php'], '', $originalContext->getBaseUrl());
        if (str_ends_with($originalBaseUrl, '/')) {
            $originalBaseUrl = substr($originalBaseUrl, 0, -1);
        }

        if ($originalBaseUrl && !$this->baseUrl) {
            // Likely in installation where the request parameters passed into this listener are not set yet so just use the original context
            return;
        }

        $context = $this->router->getContext();
        $context->setBaseUrl($this->baseUrl);
        $context->setScheme($this->scheme);
        $context->setHost($this->host);
        $context->setHttpPort($this->httpPort);
        $context->setHttpsPort($this->httpsPort);
    }
}
