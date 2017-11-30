<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\User;

use Doctrine\ORM\EntityManager;
use LightSaml\Model\Protocol\Response;
use LightSaml\SpBundle\Security\User\UserCreatorInterface;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
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
     * @var EncoderFactoryInterface
     */
    private $encoder;

    /**
     * @var
     */
    private $defaultRole;

    /**
     * @var array
     */
    private $requiredFields = ['username', 'firstname', 'lastname', 'email'];

    /**
     * UserCreator constructor.
     *
     * @param                         $entityManager
     * @param                         $userMapper
     * @param UserModel               $userModel
     * @param EncoderFactoryInterface $encoder
     * @param                         $defaultRole
     */
    public function __construct($entityManager, $userMapper, UserModel $userModel, EncoderFactoryInterface $encoder, $defaultRole)
    {
        $this->entityManager = $entityManager;
        $this->userMapper    = $userMapper;
        $this->userModel     = $userModel;
        $this->encoder       = $encoder;
        $this->defaultRole   = (int) $defaultRole;
    }

    /**
     * @param Response $response
     *
     * @return UserInterface|null
     */
    public function createUser(Response $response)
    {
        if (empty($this->defaultRole)) {
            throw new BadCredentialsException('User does not exist.');
        }

        $user = $this->userMapper->getUsername($response, true);
        $user->setPassword($this->userModel->checkNewPassword($user, $this->encoder->getEncoder($user), EncryptionHelper::generateKey()));
        $user->setRole($this->entityManager->getReference('MauticUserBundle:Role', $this->defaultRole));

        // Validate that the user has all that's required
        foreach ($this->requiredFields as $field) {
            $getter = 'get'.ucfirst($field);
            if (!$user->$getter()) {
                throw new BadCredentialsException('User does not include required fields.');
            }
        }

        $this->userModel->saveEntity($user);

        return $user;
    }
}
