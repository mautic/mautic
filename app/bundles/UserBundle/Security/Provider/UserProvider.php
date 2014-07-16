<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Provider;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;

class UserProvider implements UserProviderInterface
{
    protected $userRepository;
    protected $permissionRepository;
    protected $session;

    public function __construct(ObjectRepository $userRepository, ObjectRepository $permissionRepository, Session $session){
        $this->userRepository       = $userRepository;
        $this->permissionRepository = $permissionRepository;
        $this->session              = $session;
    }

    public function loadUserByUsername($username)
    {
        $q = $this->userRepository
            ->createQueryBuilder('u')
            ->select('u, r')
            ->leftJoin('u.role', 'r')
            ->where('u.username = :username OR u.email = :username')
            ->setParameter('username', $username)
            ->getQuery();

        $user = $q->getOneOrNullResult();

        if (empty($user)) {
            $message = sprintf(
                'Unable to find an active admin MauticUserBundle:User object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0);
        }

        //load permissions
        if ($user->getId()) {
            $permissions = $this->session->get('mautic.user.permissions', false);
            if ($permissions === false) {
                $permissions = $this->permissionRepository->getPermissionsByRole($user->getRole());
                $this->session->set('mautic.user.permissions', $permissions);
            }
            $user->setActivePermissions($permissions);
        }
        return $user;
    }


    /**
     * Refresh user; Symfony requires for authentication
     *
     * @param UserInterface $user
     * @return null|object
     * @throws \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Check to see if this provider supports a class
     *
     * @param $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }
}