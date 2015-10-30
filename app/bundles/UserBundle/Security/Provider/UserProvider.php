<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Provider;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class UserProvider
 */
class UserProvider implements UserProviderInterface
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var PermissionRepository
     */
    protected $permissionRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var EncoderFactory
     */
    protected $encoder;

    /**
     * @param UserRepository           $userRepository
     * @param PermissionRepository     $permissionRepository
     * @param Session                  $session
     * @param EventDispatcherInterface $dispatcher
     * @param EncoderFactory           $encoder
     */
    public function __construct(
        UserRepository $userRepository,
        PermissionRepository $permissionRepository,
        Session $session,
        EventDispatcherInterface $dispatcher,
        EncoderFactory $encoder
    ) {
        $this->userRepository       = $userRepository;
        $this->permissionRepository = $permissionRepository;
        $this->session              = $session;
        $this->dispatcher           = $dispatcher;
        $this->encoder              = $encoder;
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }

    /**
     * @param User $user
     */
    public function createUserIfNotExists(User $user)
    {
        if (!$user->getId()) {
            $this->updateUser($user, true);
        }
    }

    /**
     * @param User       $user
     * @param bool|false $isNew
     */
    public function updateUser(User $user, $isNew = false)
    {
        // Check for plain password
        $plainPassword = $user->getPlainPassword();
        if ($plainPassword) {
            // Encode plain text
            $user->setPassword(
                $this->encoder->getEncoder($user)->encodePassword($plainPassword, $user->getSalt())
            );
        } elseif (!$password = $user->getPassword()) {
            // Generate and encode a random password
            $user->setPassword(
                $this->encoder->getEncoder($user)->encodePassword(EncryptionHelper::generateKey(), $user->getSalt())
            );
        }

        $event = new UserEvent($user, $isNew);

        if ($this->dispatcher->hasListeners(UserEvents::USER_PRE_SAVE)) {
            $event = $this->dispatcher->dispatch(UserEvents::USER_PRE_SAVE, $event);
        }

        $this->userRepository->saveEntity($user);

        if ($this->dispatcher->hasListeners(UserEvents::USER_POST_SAVE)) {
            $this->dispatcher->dispatch(UserEvents::USER_POST_SAVE, $event);
        }
    }
}