<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Shortener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class Shortener
{
    public const SHORTENER_SERVICE = 'shortener_service';

    private CoreParametersHelper $coreParametersHelper;

    /**
     * @var ShortenerServiceInterface[]
     */
    private array $services = [];

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function addService(ShortenerServiceInterface $shortener): void
    {
        $this->services[get_class($shortener)] = $shortener;
    }

    public function getService(): ShortenerServiceInterface
    {
        $name = $this->coreParametersHelper->get(self::SHORTENER_SERVICE);

        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        throw new \InvalidArgumentException(sprintf('There is not a shortener service  %s', $name));
    }

    /**
     * @return ShortenerServiceInterface[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return ShortenerServiceInterface[]
     */
    public function getEnabledServices(): array
    {
        return array_filter($this->services, fn ($service) => $service->isEnabled());
    }

    public function shortenUrl(string $url): string
    {
        try {
            return $this->getService()->shortenUrl($url);
        } catch (\InvalidArgumentException $exception) {
            return $url;
        }
    }
}
