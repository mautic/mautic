<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
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
use LightSaml\SpBundle\Security\User\UsernameMapperInterface;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCreator implements UserCreatorInterface
{
    /** @var EntityManager */
    private $entityManager;

    /** @var UsernameMapperInterface */
    private $usernameMapper;

    /**
     * @param EntityManager           $entityManager
     * @param UsernameMapperInterface $usernameMapper
     */
    public function __construct($entityManager, $usernameMapper)
    {
        $this->entityManager  = $entityManager;
        $this->usernameMapper = $usernameMapper;
    }

    /**
     * @param Response $response
     *
     * @return UserInterface|null
     */
    public function createUser(Response $response)
    {
        $username = $this->usernameMapper->getUsername($response);

        $user = new User();
        $user->setUsername($username)
            ->setFirstName('Saml')
            ->setLastName('Saml')
            ->setPassword(1234)
            ->setEmail('saml@saml.com')
            ->setRole($this->entityManager->getReference('MauticUserBundle:Role', 1));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
