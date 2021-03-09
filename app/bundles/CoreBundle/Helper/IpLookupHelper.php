<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Symfony\Component\HttpFoundation\RequestStack;

class IpLookupHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AbstractLookup
     */
    protected $ipLookup;

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

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(
        RequestStack $requestStack,
        EntityManager $em,
        CoreParametersHelper $coreParametersHelper,
        AbstractLookup $ipLookup = null
    ) {
        $this->requestStack          = $requestStack;
        $this->em                    = $em;
        $this->ipLookup              = $ipLookup;
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
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request) {
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

                    if (false !== strpos($ip, ',')) {
                        $ip = $this->getClientIpFromProxyList($ip);
                    }

                    // Validate IP
                    if (null !== $ip && $this->ipIsValid($ip)) {
                        return $ip;
                    }
                }
            }
        }

        // if everything else fails
        return '127.0.0.1';
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
        static $ipAddresses = [];
        $request            = $this->requestStack->getCurrentRequest();

        if (null === $ip) {
            $ip = $this->getIpAddressFromRequest();
        }

        if (empty($ip) || !$this->ipIsValid($ip)) {
            //assume local as the ip is empty
            $ip = '127.0.0.1';
        }

        $this->realIp = $ip;

        if ($this->coreParametersHelper->get('anonymize_ip')) {
            $ip = preg_replace(['/\.\d*$/', '/[\da-f]*:[\da-f]*$/'], ['.***', '****:****'], $ip);
        }

        if (empty($ipAddresses[$ip])) {
            $repo      = $this->em->getRepository('MauticCoreBundle:IpAddress');
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
            if ('prod' === MAUTIC_ENV) {
                // Do not track internal IPs
                $doNotTrack = array_merge($doNotTrack, ['127.0.0.1', '::1']);
            }

            $ipAddress->setDoNotTrackList($doNotTrack);

            if ($ipAddress->isTrackable() && $request) {
                $userAgent = $request->headers->get('User-Agent', '');
                foreach ($this->doNotTrackBots as $bot) {
                    if (false !== strpos($userAgent, $bot)) {
                        $doNotTrack[] = $ip;
                        $ipAddress->setDoNotTrackList($doNotTrack);
                        continue;
                    }
                }
            }

            $details = $ipAddress->getIpDetails();
            if ($ipAddress->isTrackable() && empty($details['city']) && !$this->coreParametersHelper->get('anonymize_ip')) {
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
     *
     * @param $ip
     *
     * @return mixed
     */
    public function ipIsValid($ip)
    {
        $filterFlagNoPrivRange = $this->trackPrivateIPRanges ? 0 : FILTER_FLAG_NO_PRIV_RANGE;

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | $filterFlagNoPrivRange | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * @param $ip
     */
    protected function getClientIpFromProxyList($ip)
    {
        // Proxies are included
        $ips = explode(',', $ip);
        array_walk(
            $ips,
            function (&$val) {
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
}
