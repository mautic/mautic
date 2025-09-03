<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

class PrivateAddressChecker
{
    private const PRIVATE_IP_RANGES = [
        '10.0.0.0/8',      // RFC1918
        '172.16.0.0/12',   // RFC1918
        '192.168.0.0/16',  // RFC1918
        '127.0.0.0/8',     // Localhost
        '169.254.0.0/16',  // Link-local
        'fc00::/7',        // Unique local address
        'fe80::/10',       // Link-local address
        '::1/128',         // Localhost IPv6
    ];

    /** @var array<string> */
    private array $allowedPrivateAddresses = [];

    /**
     * @param callable|null $dnsResolver
     */
    public function __construct(
        private $dnsResolver = null,
    ) {
        $this->dnsResolver = $dnsResolver ?? 'gethostbynamel';
    }

    /**
     * @param array<string> $allowedPrivateAddresses
     */
    public function setAllowedPrivateAddresses(array $allowedPrivateAddresses): PrivateAddressChecker
    {
        $this->allowedPrivateAddresses = $allowedPrivateAddresses;

        return $this;
    }

    public function isPrivateUrl(string $url): bool
    {
        try {
            $parsedUrl = parse_url($url);

            if (!isset($parsedUrl['host'])) {
                throw new \InvalidArgumentException('Invalid URL format');
            }

            $host = strtolower($parsedUrl['host']);

            if ('localhost' === $host) {
                return true;
            }

            // Handle IPv6 addresses with brackets
            if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
                $ip = substr($host, 1, -1); // Remove brackets

                return $this->isPrivateIp($ip);
            }

            if (!filter_var($host, FILTER_VALIDATE_IP)) {
                $ips = ($this->dnsResolver)($host);
                if (false === $ips) {
                    throw new \InvalidArgumentException('Could not resolve hostname');
                }
                foreach ($ips as $ip) {
                    if ($this->isPrivateIp($ip)) {
                        return true;
                    }
                }

                return false;
            }

            return $this->isPrivateIp($host);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('URL validation failed: '.$e->getMessage());
        }
    }

    public function isPrivateIp(string $ip): bool
    {
        // Remove zone index if present
        if (($pos = strpos($ip, '%')) !== false) {
            $ip = substr($ip, 0, $pos);
        }

        $binaryIp = @inet_pton($ip);
        if (false === $binaryIp) {
            return false;
        }

        foreach (self::PRIVATE_IP_RANGES as $range) {
            [$networkIp, $netmask] = explode('/', $range);

            $binaryNetwork = @inet_pton($networkIp);
            if (false === $binaryNetwork) {
                continue;
            }

            $maskLen  = (int) $netmask;
            $numBytes = (int) ($maskLen / 8);
            $numBits  = $maskLen % 8;

            if (substr($binaryIp, 0, $numBytes) !== substr($binaryNetwork, 0, $numBytes)) {
                continue;
            }

            if ($numBits > 0) {
                $mask = 0xFF << (8 - $numBits);
                if ((ord($binaryIp[$numBytes]) & $mask) !== (ord($binaryNetwork[$numBytes]) & $mask)) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Checks if the given URL is allowed based on the allowed private addresses list.
     * Returns true if the URL is either public or in the allowed private addresses list.
     */
    public function isAllowedUrl(string $url): bool
    {
        try {
            // If it's not a private URL, it's allowed
            if (!$this->isPrivateUrl($url)) {
                return true;
            }

            // If no allowed private addresses are set, all private URLs are forbidden
            if (empty($this->allowedPrivateAddresses)) {
                return false;
            }

            $parsedUrl = parse_url($url);
            if (!isset($parsedUrl['host'])) {
                throw new \InvalidArgumentException('Invalid URL format');
            }

            $host = strtolower($parsedUrl['host']);

            // Check if the host is directly in allowed addresses
            if (in_array($host, $this->allowedPrivateAddresses, true)) {
                return true;
            }

            // Handle IPv6 addresses with brackets
            if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
                $host = substr($host, 1, -1); // Remove brackets
            }

            // If the host is an IP address, check if it's in allowed addresses
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return in_array($host, $this->allowedPrivateAddresses, true);
            }

            // Resolve hostname to IPs and check if any are in allowed addresses
            $ips = ($this->dnsResolver)($host);
            if (false === $ips) {
                throw new \InvalidArgumentException('Could not resolve hostname');
            }

            foreach ($ips as $ip) {
                if (in_array($ip, $this->allowedPrivateAddresses, true)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('URL validation failed: '.$e->getMessage());
        }
    }
}
