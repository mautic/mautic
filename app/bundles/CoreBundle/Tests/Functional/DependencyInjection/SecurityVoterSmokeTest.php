<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class SecurityVoterSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * Local security voters in the container, to catch a voter that silently stops being registered.
     *
     * A voter left out of the container never denies anything, so an access check it owns turns into a
     * silent "granted" - nothing fails, the permission is simply gone.
     *
     * @var string[]
     */
    private const EXPECTED_SECURITY_VOTER_CLASSES = [
        \Mautic\ApiBundle\Security\Voter\ApiPermissionVoter::class,
    ];

    public function testSecurityVotersAreRegistered(): void
    {
        $securityVoterClasses = array_map(
            fn (VoterInterface $securityVoter): string => $securityVoter::class,
            $this->resolveSecurityVoters()
        );

        $securityVoterClasses = array_unique($securityVoterClasses);
        sort($securityVoterClasses);

        $this->assertSame(self::EXPECTED_SECURITY_VOTER_CLASSES, array_values($securityVoterClasses));
    }

    /**
     * @return array<int, VoterInterface>
     */
    private function resolveSecurityVoters(): array
    {
        return array_filter(
            $this->createAllServices(),
            fn (object $service): bool => $service instanceof VoterInterface && $this->isLocalService($service)
        );
    }
}
