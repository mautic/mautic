<?php


namespace Mautic\CoreBundle\Shortener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Shortener\CustomApi\CustomApiShortener;

class Shortener
{
    public const SHORTENER_SERVICE = 'shortener_service';

    private CoreParametersHelper $coreParametersHelper;

    private CustomApiShortener $customApiShortener;

    /**
     * @var ShortenerServiceInterface[]
     */
    private array $services = [];

    public function __construct(CoreParametersHelper $coreParametersHelper, CustomApiShortener $customApiShortener)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->customApiShortener   = $customApiShortener;
    }

    public function addService(string $id, ShortenerServiceInterface $shortener)
    {
        $this->services[$id] = $shortener;
    }

    public function getService(string $name = null): ShortenerServiceInterface
    {
        if (!$name) {
            $name = $this->coreParametersHelper->get(self::SHORTENER_SERVICE);
        }

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

    public function getEnabledServices(): array
    {
        $enabledServices = [];

        foreach ($this->services as $name => $service) {
            if ($service->isEnabled()) {
                $enabledServices[$name] = $service;
            }
        }

        return $enabledServices;
    }

    public function shortenUrl(string $url): string
    {
        try {
            return $this->getService()->shortenUrl($url);
        } catch (\InvalidArgumentException $exception) {
            return $this->customApiShortener->shortenUrl($url);
        }
    }
}
