<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Stat;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BotRatioHelper
{
    /**
     * @param string[] $blockedUserAgents
     * @param string[] $blockedIPAddresses
     */
    public function __construct(
        #[Autowire(env: 'float:MAUTIC_BOT_HELPER_BOT_RATIO_THRESHOLD')]
        private float $botRatioThreshold = 0.6,

        #[Autowire(env: 'int:MAUTIC_BOT_HELPER_TIME_EMAIL_THRESHOLD')]
        private int $timeFromEmailThreshold = 2,

        #[Autowire(env: 'json:MAUTIC_BOT_HELPER_BLOCKED_USER_AGENTS')]
        private array $blockedUserAgents = [],

        #[Autowire(env: 'json:MAUTIC_BOT_HELPER_BLOCKED_IP_ADDRESSES')]
        private array $blockedIPAddresses = [],
    ) {
    }

    public function isHitByBot(Stat $emailStat, \DateTimeInterface $emailHitDateTime, IpAddress $ipAddress, string $userAgent): bool
    {
        $totalPoints = (int) $this->isUnderTimeThreshold($emailStat, $emailHitDateTime) +
            (int) $this->isIpInIgnoreList($ipAddress) +
            (int) $this->isUserAgentInIgnoreList($userAgent);

        return $totalPoints / 3 >= $this->botRatioThreshold;
    }

    private function isUnderTimeThreshold(Stat $emailStat, \DateTimeInterface $emailHitDateTime): bool
    {
        $timeFromSend = $emailHitDateTime->getTimestamp() - $emailStat->getDateSent()->getTimestamp();

        return $timeFromSend < $this->timeFromEmailThreshold;
    }

    private function isIpInIgnoreList(IpAddress $ipAddress): bool
    {
        // Create a clone so that setting up do not track IP list here will not update original blocked Ip List
        $ipAddressLocal = clone $ipAddress;
        $ipAddressLocal->setDoNotTrackList($this->blockedIPAddresses);

        return !$ipAddressLocal->isTrackable();
    }

    private function isUserAgentInIgnoreList(string $userAgent): bool
    {
        foreach ($this->blockedUserAgents as $blockedUserAgent) {
            if (str_contains($userAgent, $blockedUserAgent)) {
                return true;
            }
        }

        return false;
    }
}
