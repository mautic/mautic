<?php

namespace Mautic\PageBundle\Helper;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;

class TrackingHelper
{
    public function __construct(
        protected ContactTracker $contactTracker,
        protected CacheProvider $cache,
        protected CoreParametersHelper $coreParametersHelper,
        protected RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<string, 'facebook_pixel'|'google_analytics'>
     */
    public function getEnabledServices(): array
    {
        $keys = [
            'google_analytics' => 'Google Analytics',
            'facebook_pixel'   => 'Facebook Pixel',
        ];
        $result = [];
        foreach ($keys as $key => $service) {
            if ($id = $this->coreParametersHelper->get($key.'_id')) {
                $result[$service] = $key;
            }
        }

        return $result;
    }

    /**
     * @return string|null
     */
    private function getCacheKey()
    {
        $lead = $this->contactTracker->getContact();

        return $lead instanceof Lead ? 'mtc-tracking-pixel-events-'.$lead->getId() : null;
    }

    /**
     * @param mixed[] $values
     *
     * @throws InvalidArgumentException
     */
    public function updateCacheItem(array $values): void
    {
        $cacheKey = $this->getCacheKey();
        if (null !== $cacheKey) {
            $item = $this->cache->getItem($cacheKey);
            $item->set(serialize(array_merge($values, $this->getCacheItem())));
            $item->expiresAfter(86400); // one day in seconds

            $this->cache->save($item);
        }
    }

    /**
     * @return mixed[]
     *
     * @throws InvalidArgumentException
     */
    public function getCacheItem(bool $remove = false): array
    {
        $cacheKey   = $this->getCacheKey();
        $cacheValue = [];

        /* @var CacheItemInterface $item */
        if (null !== $cacheKey) {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                $cacheValue = Serializer::decode($item->get(), ['allowed_classes' => false]);
                if ($remove) {
                    $this->cache->deleteItem($cacheKey);
                }
            }
        }

        return (array) $cacheValue;
    }

    /**
     * @return bool|mixed
     */
    public function displayInitCode($service)
    {
        $pixelId = $this->coreParametersHelper->get($service.'_id');

        if ($pixelId && $this->coreParametersHelper->get($service.'_landingpage_enabled') && $this->isLandingPage()) {
            return $pixelId;
        }
        if ($pixelId && $this->coreParametersHelper->get($service.'_trackingpage_enabled') && !$this->isLandingPage()) {
            return $pixelId;
        }

        return false;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->contactTracker->getContact();
    }

    public function getAnonymizeIp()
    {
        return $this->coreParametersHelper->get('google_analytics_anonymize_ip');
    }

    protected function isLandingPage(): bool
    {
        $server = $this->requestStack->getCurrentRequest()->server;
        if (!str_contains((string) $server->get('HTTP_REFERER'), $this->coreParametersHelper->get('site_url'))) {
            return false;
        }

        return true;
    }
}
