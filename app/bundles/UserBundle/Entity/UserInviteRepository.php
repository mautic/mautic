<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<UserInvite>
 */
final class UserInviteRepository extends CommonRepository implements UserInviteRepositoryInterface
{
    public function findOneByTokenSelector(string $selector): ?UserInvite
    {
        $invite = $this->findOneBy(['tokenSelector' => $selector]);
        \assert(null === $invite || $invite instanceof UserInvite);

        return $invite;
    }

    public function revokeOutstandingInvites(string $email): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->update(UserInvite::class, 'invite')
            ->set('invite.used', ':used')
            ->where('invite.email = :email')
            ->andWhere('invite.used = :unused')
            ->setParameter('used', true, Types::BOOLEAN)
            ->setParameter('unused', false, Types::BOOLEAN)
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();
    }
}
