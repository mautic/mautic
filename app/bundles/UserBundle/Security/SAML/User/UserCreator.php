<?php

namespace Mautic\UserBundle\Security\SAML\User;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use LightSaml\Model\Protocol\Response;
use LightSaml\SpBundle\Security\User\UserCreatorInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCreator implements UserCreatorInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserMapper
     */
    private $userMapper;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var UserPasswordHasher
     */
    private $hasher;

    /**
     * @var int
     */
    private $defaultRole;

    /**
     * @var array
     */
    private $requiredFields = [
        'username',
        'firstname',
        'lastname',
        'email',
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        UserMapper $userMapper,
        UserModel $userModel,
        UserPasswordHasher $hasher,
        $defaultRole
    ) {
        $this->entityManager = $entityManager;
        $this->userMapper    = $userMapper;
        $this->userModel     = $userModel;
        $this->hasher        = $hasher;
        $this->defaultRole   = (int) $defaultRole;
    }

    /**
     * @return UserInterface|null
     */
    public function createUser(Response $response): User
    {
        if (empty($this->defaultRole)) {
            throw new BadCredentialsException('User does not exist.');
        }

        /** @var Role $defaultRole */
        $defaultRole = $this->entityManager->getReference(\Mautic\UserBundle\Entity\Role::class, $this->defaultRole);

        $user = $this->userMapper->getUser($response);
        $user->setPassword($this->userModel->checkNewPassword($user, $this->hasher, EncryptionHelper::generateKey()));
        $user->setRole($defaultRole);

        $this->validateUser($user);

        $this->userModel->saveEntity($user);

        return $user;
    }

    /**
     * @throws BadCredentialsException
     */
    private function validateUser(User $user): void
    {
        // Validate that the user has all that's required
        foreach ($this->requiredFields as $field) {
            $getter = 'get'.ucfirst($field);

            if (!$user->$getter()) {
                throw new BadCredentialsException('User does not include required fields.');
            }
        }
    }
}
