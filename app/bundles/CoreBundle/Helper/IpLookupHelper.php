<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

class IpLookupHelper implements ResetInterface
{
    /**
     * @var array
     */
    protected $doNotTrackIps;

    /**
     * @var array
     */
    protected $doNotTrackBots;

    /**
     * @var array
     */
    protected $doNotTrackInternalIps;

    /**
     * @var array
     */
    protected $trackPrivateIPRanges;

    /**
     * @var string
     */
    private $realIp;

    private readonly CoreParametersHelper $coreParametersHelper;

    /**
     * @var array<string, IpAddress>
     */
    private static array $ipAddresses = [];

    /**
     * @var array<string, bool>
     */
    private array $requestTrackability = [];

    public function __construct(
        protected RequestStack $requestStack,
        protected IpAddressRepository $ipAddressRepository,
        CoreParametersHelper $coreParametersHelper,
        private readonly DeviceDetectorFactoryInterface $deviceDetectorFactory,
        protected ?AbstractLookup $ipLookup = null,
    ) {
        $this->doNotTrackIps         = $coreParametersHelper->get('do_not_track_ips');
        $this->doNotTrackBots        = $coreParametersHelper->get('do_not_track_bots');
        $this->doNotTrackInternalIps = $coreParametersHelper->get('do_not_track_internal_ips');
        $this->trackPrivateIPRanges  = $coreParametersHelper->get('track_private_ip_ranges');
        $this->coreParametersHelper  = $coreParametersHelper;
    }

    /**
     * Guess the IP address from current session.
     *
     * @return string
     */
    public function getIpAddressFromRequest()
    {
        $request = $this->getRequest();

        return null !== $request ? $this->getIpAddressFromRequestObject($request) : '127.0.0.1';
    }

    /**
     * Get an IpAddress entity for current session or for passed in IP address.
     *
     * @param string|null $ip
     *
     * @return IpAddress
     */
    public function getIpAddress($ip = null)
    {
        $isIpAnonymizationEnabled = (bool) $this->coreParametersHelper->get('anonymize_ip');

        if (null === $ip) {
            $ip = $this->getIpAddressFromRequest();
        }

        if (empty($ip) || !$this->ipIsValid($ip)) {
            // assume local as the ip is empty
            $ip = '127.0.0.1';
        }

        $this->realIp = $ip;

        if ($isIpAnonymizationEnabled) {
            $ip = '*.*.*.*';
        }

        $ipAddress = $this->getCachedIpAddress($ip, $isIpAnonymizationEnabled);

        if ($ipAddress->isTrackable() && ($request = $this->getRequest())) {
            $this->applyUserAgentClassification($ipAddress, $ip, $request);
        }

        return $ipAddress;
    }

    /**
     * Get an IP address entity for a request without storing request-specific bot classification on it.
     *
     * This is used for asynchronous requests where the request that caused the hit is not in the
     * current RequestStack. The entity remains safe to use for persistence while the request-specific
     * User-Agent classification is kept separate in isRequestTrackableForRequest().
     */
    public function getIpAddressForRequest(Request $request): IpAddress
    {
        $isIpAnonymizationEnabled = (bool) $this->coreParametersHelper->get('anonymize_ip');
        $ip                         = $this->getIpAddressFromRequestObject($request);

        if (empty($ip) || !$this->ipIsValid($ip)) {
            $ip = '127.0.0.1';
        }

        $this->realIp = $ip;

        if ($isIpAnonymizationEnabled) {
            $ip = '*.*.*.*';
        }

        $ipAddress = $this->getCachedIpAddress($ip, $isIpAnonymizationEnabled);
        $ipAddress->setDoNotTrackList($this->getConfiguredDoNotTrackList());

        return $ipAddress;
    }

    /**
     * Determine if a supplied request should be tracked without mutating the managed IP entity.
     */
    public function isRequestTrackableForRequest(Request $request, bool $checkDeviceDetector = true): bool
    {
        if ($request->isMethod('HEAD')) {
            return false;
        }

        $purpose = $request->headers->get('Purpose') ?? $request->headers->get('Sec-Purpose');
        if ($purpose && in_array(strtolower($purpose), ['prefetch', 'prerender'], true)) {
            return false;
        }

        $secGpc = trim((string) ($request->headers->get('Sec-GPC') ?? $request->server->get('HTTP_SEC_GPC')));
        if ('1' === $secGpc) {
            return false;
        }

        $dnt = trim((string) ($request->headers->get('DNT') ?? $request->server->get('HTTP_DNT')));
        if ('1' === $dnt) {
            return false;
        }

        $ipAddress = $this->getIpAddressForRequest($request);
        if (!$ipAddress->isTrackable()) {
            return false;
        }

        $userAgent = $request->headers->get('User-Agent', '');
        foreach ($this->getDoNotTrackBots() as $bot) {
            if (str_contains($userAgent, $bot)) {
                return false;
            }
        }

        if (!$checkDeviceDetector) {
            return true;
        }

        $cacheKey = $ipAddress->getIpAddress().'|'.(int) $checkDeviceDetector.'|'.hash('sha256', $userAgent);
        if (isset($this->requestTrackability[$cacheKey])) {
            return $this->requestTrackability[$cacheKey];
        }

        $deviceDetector = $this->deviceDetectorFactory->create($userAgent);
        $deviceDetector->parse();

        return $this->requestTrackability[$cacheKey] = !$deviceDetector->isBot();
    }

    /**
     * @return array<int, string>
     */
    private function getConfiguredDoNotTrackList(): array
    {
        if (!is_array($this->doNotTrackIps)) {
            $this->doNotTrackIps = [];
        }

        if (!is_array($this->doNotTrackInternalIps)) {
            $this->doNotTrackInternalIps = [];
        }

        return array_merge($this->doNotTrackIps, $this->doNotTrackInternalIps);
    }

    /**
     * @return array<int, string>
     */
    private function getDoNotTrackBots(): array
    {
        if (!is_array($this->doNotTrackBots)) {
            $this->doNotTrackBots = [];
        }

        return $this->doNotTrackBots;
    }

    private function getIpAddressFromRequestObject(Request $request): string
    {
        $ipHolders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ipHolders as $key) {
            if ($request->server->get($key)) {
                $ip = trim($request->server->get($key));

                if (str_contains($ip, ',')) {
                    $ip = $this->getClientIpFromProxyList($ip);
                }

                if (null !== $ip && $this->ipIsValid($ip)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    private function applyUserAgentClassification(IpAddress $ipAddress, string $ip, Request $request): void
    {
        $doNotTrack = $this->getConfiguredDoNotTrackList();
        $userAgent  = $request->headers->get('User-Agent', '');

        foreach ($this->getDoNotTrackBots() as $bot) {
            if (str_contains($userAgent, $bot)) {
                $doNotTrack[] = $ip;
                $ipAddress->setDoNotTrackList($doNotTrack);
                break;
            }
        }

        // second check for bots  https://github.com/matomo-org/device-detector
        $deviceDetector = $this->deviceDetectorFactory->create($userAgent);
        $deviceDetector->parse();
        if ($deviceDetector->isBot()) {
            $doNotTrack[] = $ip;
            $ipAddress->setDoNotTrackList($doNotTrack);
        }
    }

    private function getCachedIpAddress(string $ip, bool $isIpAnonymizationEnabled): IpAddress
    {
        if (isset(self::$ipAddresses[$ip])) {
            return self::$ipAddresses[$ip];
        }

        $ipAddress = $this->ipAddressRepository->findOneByIpAddress($ip);
        $saveIp    = null === $ipAddress;

        if (null === $ipAddress) {
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($ip);
        }

        $ipAddress->setDoNotTrackList($this->getConfiguredDoNotTrackList());

        $details = $ipAddress->getIpDetails();
        if ($ipAddress->isTrackable() && !$isIpAnonymizationEnabled && empty($details['city'])) {
            if ($this->ipLookup) {
                $details = $this->getIpDetails($ip);
                $ipAddress->setIpDetails($details);
                $saveIp = true;
            }
        }

        if ($saveIp) {
            $this->ipAddressRepository->saveEntity($ipAddress);
        }

        return self::$ipAddresses[$ip] = $ipAddress;
    }

    /**
     * @param string $ip
     *
     * @return array
     */
    public function getIpDetails($ip)
    {
        if ($this->ipLookup) {
            return $this->ipLookup->setIpAddress($ip)->getDetails();
        }

        return [];
    }

    /**
     * Validates if an IP address if valid.
     */
    public function ipIsValid($ip): string|bool
    {
        $filterFlagNoPrivRange = $this->trackPrivateIPRanges ? 0 : FILTER_FLAG_NO_PRIV_RANGE;

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | $filterFlagNoPrivRange | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Resets cache.
     */
    public function reset(): void
    {
        self::$ipAddresses = [];
        $this->requestTrackability = [];
    }

    protected function getClientIpFromProxyList($ip)
    {
        // Proxies are included
        $ips = explode(',', $ip);
        array_walk(
            $ips,
            function (&$val): void {
                $val = trim($val);
            }
        );

        if ($this->doNotTrackInternalIps) {
            $ips = array_diff($ips, $this->doNotTrackInternalIps);
        }

        // https://en.wikipedia.org/wiki/X-Forwarded-For
        // X-Forwarded-For: client, proxy1, proxy2
        foreach ($ips as $ip) {
            if ($this->ipIsValid($ip)) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getRealIp()
    {
        return $this->realIp;
    }

    /**
     * Determine if the current request should be tracked.
     *
     * Checks for privacy signals and bot indicators:
     * - HEAD requests (bots/monitoring tools)
     * - Prefetch/prerender requests (browser speculation)
     * - Sec-GPC: 1 (Global Privacy Control - legally required by CCPA)
     * - DNT: 1 (Do Not Track - user preference)
     * - Known bots (existing IP/User-Agent filtering)
     */
    public function isRequestTrackable(): bool
    {
        $request = $this->getRequest();

        if (null === $request) {
            return $this->getIpAddress()->isTrackable();
        }

        return $this->isRequestTrackableForRequest($request);
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
