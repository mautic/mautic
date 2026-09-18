<?php

namespace Mautic\UserBundle\Security\OIDC\User;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\OpenIdBundle\Entity\SubjectId;
use Mautic\OpenIdBundle\Repository\SubjectIdRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\Exception\OpenIdConnectIdTakenException;
use Mautic\UserBundle\Security\OIDC\Exception\UserTakenException;

final class Linker implements LinkerInterface
{
    private EntityManagerInterface $entityManager;
    private SubjectIdRepository $subjectIdRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager       = $entityManager;
        $subjectIdRepository       = $entityManager->getRepository(SubjectId::class);
        \assert($subjectIdRepository instanceof SubjectIdRepository);
        $this->subjectIdRepository = $subjectIdRepository;
    }

    public function findLinkedUser(string $identifier, ?User $user): ?User
    {
        $subjectId = $this->subjectIdRepository->findOneBy(['subjectID' => $identifier]);

        if (!$subjectId) {
            return null;
        }

        if ($user && $subjectId->getUser()->getId() !== $user->getId()) {
            throw new OpenIdConnectIdTakenException('mautic.open_id.login.exception.open_id_taken');
        }

        return $subjectId->getUser();
    }

    public function linkToUser(string $identifier, User $user, bool $flush = true): User
    {
        if ($this->subjectIdRepository->count(['subjectID' => $identifier])) {
            throw new OpenIdConnectIdTakenException('mautic.open_id.link.exception.open_id_taken');
        }

        if ($this->subjectIdRepository->count(['user' => $user->getId()])) {
            throw new UserTakenException('mautic.open_id.link.exception.user_taken');
        }

        $subjectIdEntity = new SubjectId();
        $subjectIdEntity->setSubjectID($identifier);
        $subjectIdEntity->setUser($user);
        $this->subjectIdRepository->saveEntity($subjectIdEntity, $flush);

        return $user;
    }

    public function editLinkToUser(SubjectId $subjectId, User $user, bool $flush = true): void
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
