<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\User;

use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\User;

interface LinkerInterface
{
    public function findLinkedUser(string $identifier, ?User $user): ?User;

    public function linkToUser(string $identifier, User $user): User;

    public function editLinkToUser(OidcSubjectId $subjectId, User $user, bool $flush = true): void;

    public function unlink(User $user, bool $flush = true): void;
}
