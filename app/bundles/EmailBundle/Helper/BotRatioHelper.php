<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use Mautic\PageBundle\Entity\HitRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BotRatioHelper
{
    /**
     * @param string[] $blockedUserAgents
     * @param string[] $blockedIPAddresses
     */
    public function __construct(
        private readonly DeviceDetectorFactoryInterface $deviceDetectorFactory,

        #[Autowire(env: 'float:MAUTIC_BOT_HELPER_BOT_RATIO_THRESHOLD')]
        private readonly float $botRatioThreshold = 0.6,

        #[Autowire(env: 'int:MAUTIC_BOT_HELPER_TIME_EMAIL_THRESHOLD')]
        private readonly int $timeFromEmailThreshold = 2,

        #[Autowire(env: 'json:MAUTIC_BOT_HELPER_BLOCKED_USER_AGENTS')]
        private readonly array $blockedUserAgents = [],

        #[Autowire(env: 'json:MAUTIC_BOT_HELPER_BLOCKED_IP_ADDRESSES')]
        private readonly array $blockedIPAddresses = [],

        #[Autowire(env: 'int:MAUTIC_BOT_HELPER_BURST_MAX_DISTINCT_IPS')]
        private readonly int $burstMaxDistinctIps = 0,

        #[Autowire(env: 'int:MAUTIC_BOT_HELPER_BURST_WINDOW_SECONDS')]
        private readonly int $burstWindowSeconds = 30,

        private readonly ?HitRepository $hitRepository = null,
    ) {
    }

    public function isHitByBot(
        Stat $emailStat,
        \DateTimeInterface $emailHitDateTime,
        IpAddress $ipAddress,
        string $userAgent,
    ): bool {
        if ($this->isBurstScanner($emailStat, $emailHitDateTime)) {
            return true;
        }

        $totalPoints = (int) $this->isUnderTimeThreshold($emailStat, $emailHitDateTime) +
            (int) $this->isIpInIgnoreList($ipAddress) +
            (int) $this->isUserAgentInIgnoreList($userAgent);

        return $totalPoints / 3 >= $this->botRatioThreshold;
    }

    private function isBotByMatomoDetector(string $userAgent): bool
    {
        $deviceDetector = $this->deviceDetectorFactory->create($userAgent);
        $deviceDetector->parse();

        return $deviceDetector->isBot();
    }

   
    private function isBurstScanner(Stat $emailStat, \DateTimeInterface $emailHitDateTime): bool
    {
        if ($this->burstMaxDistinctIps <= 0 || null === $this->hitRepository) {
            return false;
        }

        $email = $emailStat->getEmail();
        $lead  = $emailStat->getLead();

        if (null === $email || null === $lead) {
            return false;
        }

        $emailId = $email->getId();
        $leadId  = $lead->getId();

        if (null === $emailId || $leadId <= 0) {
            return false;
        }

        $since = (new \DateTimeImmutable('@'.($emailHitDateTime->getTimestamp() - $this->burstWindowSeconds)))
            ->setTimezone(new \DateTimeZone('UTC'));

        $distinctIps = $this->hitRepository->countDistinctIpsForEmailAndLeadSince(
            (int) $emailId,
            $leadId,
            $since
        );

        return $distinctIps >= $this->burstMaxDistinctIps;
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
        if ($this->isBotByMatomoDetector($userAgent)) {
            return true;
        }

        foreach ($this->blockedUserAgents as $blockedUserAgent) {
            if (str_contains($userAgent, $blockedUserAgent)) {
                return true;
            }
        }

        return false;
    }
}
