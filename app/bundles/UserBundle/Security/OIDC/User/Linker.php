<?php

namespace Mautic\UserBundle\Security\OIDC\User;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Exception\OidcIdTakenException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(LinkerInterface::class)]
final readonly class Linker implements LinkerInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private OidcSubjectIdRepository $subjectIdRepository)
    {
    }

    public function findLinkedUser(string $identifier, ?User $user): ?User
    {
        $subjectId = $this->subjectIdRepository->findOneBy(['subjectID' => $identifier]);

        if (!$subjectId) {
            return null;
        }

        if ($user && $subjectId->getUser()->getId() !== $user->getId()) {
            throw new OidcIdTakenException('mautic.open_id.login.exception.open_id_taken');
        }

        return $subjectId->getUser();
    }

    public function linkToUser(string $identifier, User $user, bool $flush = true): User
    {
        if ($this->subjectIdRepository->count(['subjectID' => $identifier])) {
            throw new OidcIdTakenException('mautic.open_id.link.exception.open_id_taken');
        }

        if ($this->subjectIdRepository->count(['user' => $user->getId()])) {
            throw new OidcException('mautic.open_id.link.exception.user_taken');
        }

        $subjectIdEntity = new OidcSubjectId($user, $identifier);
        $this->subjectIdRepository->saveEntity($subjectIdEntity, $flush);

        return $user;
    }

    public function editLinkToUser(OidcSubjectId $subjectId, User $user, bool $flush = true): void
    {
        if ($subjectId->getSubjectID()) {
            // persist the user to generate the ID when creating a new user
            // don't flush here, it will be called by the user bundle to save the user
            $this->entityManager->persist($user);
            $this->findLinkedUser($subjectId->getSubjectID(), $user);
            $this->entityManager->persist($subjectId);

            return;
        }

        if ($user->getId()) {
            $this->unlink($user, $flush);
        }
    }

    public function unlink(User $user, bool $flush = true): void
    {
        $subjectId = $this->subjectIdRepository->findOneBy(['user' => $user->getId()]);

        if (!$subjectId) {
            return;
        }

        $this->subjectIdRepository->deleteEntity($subjectId, $flush);
    }
}
