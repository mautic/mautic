<?php

namespace Mautic\CoreBundle\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Symfony\Component\HttpFoundation\RequestStack;

class IpLookupHelper
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

    private CoreParametersHelper $coreParametersHelper;

    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManager $em,
        CoreParametersHelper $coreParametersHelper,
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
        // Use Symfony's request stack to get the client IP
        // This will ensure that trusted proxies are correctly handled
        // And it will ensure that only trusted headers are handled
        // @see https://symfony.com/doc/5.x/create_framework/http_foundation.html
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $request->getClientIp() : '127.0.0.1';
    }

    /**
     * Get an IpAddress entity for current session or for passed in IP address.
     *
     * @param string $ip
     *
     * @return IpAddress
     */
    public function getIpAddress($ip = null)
    {
        static $ipAddresses       = [];
        $request                  = $this->requestStack->getCurrentRequest();
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

        if (empty($ipAddresses[$ip])) {
            $ipAddress = null;
            $saveIp    = false;

            /** @var IpAddressRepository $repo */
            $repo      = $this->em->getRepository(IpAddress::class);
            $ipAddress = $repo->findOneByIpAddress($ip);
            $saveIp    = (null === $ipAddress);

            if (null === $ipAddress) {
                $ipAddress = new IpAddress();
                $ipAddress->setIpAddress($ip);
            }

            // Ensure the do not track list is inserted
            if (!is_array($this->doNotTrackIps)) {
                $this->doNotTrackIps = [];
            }

            if (!is_array($this->doNotTrackBots)) {
                $this->doNotTrackBots = [];
            }

            if (!is_array($this->doNotTrackInternalIps)) {
                $this->doNotTrackInternalIps = [];
            }

            $doNotTrack = array_merge($this->doNotTrackIps, $this->doNotTrackInternalIps);

            $ipAddress->setDoNotTrackList($doNotTrack);

            if ($ipAddress->isTrackable() && $request) {
                $userAgent = $request->headers->get('User-Agent', '');
                foreach ($this->doNotTrackBots as $bot) {
                    if (str_contains($userAgent, $bot)) {
                        $doNotTrack[] = $ip;
                        $ipAddress->setDoNotTrackList($doNotTrack);
                        continue;
                    }
                }
            }

            $details = $ipAddress->getIpDetails();
            if ($ipAddress->isTrackable() && !$isIpAnonymizationEnabled && empty($details['city'])) {
                // Get the IP lookup service

                // Fetch the data
                if ($this->ipLookup) {
                    $details = $this->getIpDetails($ip);

                    $ipAddress->setIpDetails($details);

                    // Save new details
                    $saveIp = true;
                }
            }

            if ($saveIp) {
                $repo->saveEntity($ipAddress);
            }

            $ipAddresses[$ip] = $ipAddress;
        }

        return $ipAddresses[$ip];
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
     * @return string
     */
    public function getRealIp()
    {
        return $this->realIp;
    }
}
