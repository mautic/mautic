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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class IpLookupHelper.
 */
class IpLookupHelper
{
    /**
     * @var null|Request
     */
    protected $request;

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
     * IpLookupHelper constructor.
     *
     * @param RequestStack         $requestStack
     * @param EntityManager        $em
     * @param CoreParametersHelper $coreParametersHelper
     * @param AbstractLookup       $ipLookup
     */
    public function __construct(
        RequestStack $requestStack,
        EntityManager $em,
        CoreParametersHelper $coreParametersHelper,
        AbstractLookup $ipLookup = null
    ) {
        $this->request               = $requestStack->getCurrentRequest();
        $this->em                    = $em;
        $this->ipLookup              = $ipLookup;
        $this->doNotTrackIps         = $coreParametersHelper->getParameter('mautic.do_not_track_ips');
        $this->doNotTrackBots        = $coreParametersHelper->getParameter('mautic.do_not_track_bots');
        $this->doNotTrackInternalIps = $coreParametersHelper->getParameter('mautic.do_not_track_internal_ips');
    }

    /**
     * Guess the IP address from current session.
     *
     * @return string
     */
    public function getIpAddressFromRequest()
    {
        if (null !== $this->request) {
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
                if ($this->request->server->get($key)) {
                    $ip = trim($this->request->server->get($key));

                    if (strpos($ip, ',') !== false) {
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

        if ($ip === null) {
            $ip = $this->getIpAddressFromRequest();
        }

        if (empty($ip) || !$this->ipIsValid($ip)) {
            //assume local as the ip is empty
            $ip = '127.0.0.1';
        }

        if (empty($ipAddresses[$ip])) {
            $repo      = $this->em->getRepository('MauticCoreBundle:IpAddress');
            $ipAddress = $repo->findOneByIpAddress($ip);
            $saveIp    = ($ipAddress === null);

            if ($ipAddress === null) {
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

            if ($ipAddress->isTrackable() && $this->request) {
                $userAgent = $this->request->headers->get('User-Agent');
                foreach ($this->doNotTrackBots as $bot) {
                    if (strpos($userAgent, $bot) !== false) {
                        $doNotTrack[] = $ip;
                        $ipAddress->setDoNotTrackList($doNotTrack);
                        continue;
                    }
                }
            }

            $details = $ipAddress->getIpDetails();
            if ($ipAddress->isTrackable() && empty($details['city'])) {
                // Get the IP lookup service

                // Fetch the data
                if ($this->ipLookup) {
                    $details = $this->ipLookup->setIpAddress($ip)
                        ->getDetails();

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
     * Validates if an IP address if valid.
     *
     * @param $ip
     *
     * @return mixed
     */
    public function ipIsValid($ip)
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
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
}
