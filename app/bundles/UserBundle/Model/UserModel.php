<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserToken;
use Mautic\UserBundle\Enum\UserTokenAuthorizator;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\Form\Type\UserType;
use Mautic\UserBundle\Model\UserToken\UserTokenServiceInterface;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserModel extends FormModel
{
    /**
     * @var MailHelper
     */
    protected $mailHelper;

    /**
     * @var UserTokenServiceInterface
     */
    private $userTokenService;

    public function __construct(
        MailHelper $mailHelper,
        UserTokenServiceInterface $userTokenService
    ) {
        $this->mailHelper       = $mailHelper;
        $this->userTokenService = $userTokenService;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->em->getRepository(User::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'user:users';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(['User'], 'Entity must be of class User()');
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Get a list of users for an autocomplete input.
     *
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param array  $permissionLimiter
     *
     * @return array
     */
    public function getUserList($search = '', $limit = 10, $start = 0, $permissionLimiter = [])
    {
        return $this->getRepository()->getUserList($search, $limit, $start, $permissionLimiter);
    }

    /**
     * Checks for a new password and rehashes if necessary.
     *
     * @param string     $submittedPassword
     * @param bool|false $validate
     *
     * @return string
     */
    public function checkNewPassword(User $entity, UserPasswordEncoder $encoder, $submittedPassword, $validate = false)
    {
        if ($validate) {
            if (strlen($submittedPassword) < 6) {
                throw new \InvalidArgumentException($this->translator->trans('mautic.user.user.password.minlength', 'validators'));
            }
        }

        if (!empty($submittedPassword)) {
            //hash the clear password submitted via the form
            return $encoder->encodePassword($entity, $submittedPassword);
        }

        return $entity->getPassword();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(['User'], 'Entity must be of class User()');
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(UserType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new User();
        }

        $entity = parent::getEntity($id);

        if ($entity) {
            //add user's permissions
            $entity->setActivePermissions(
                $this->em->getRepository('MauticUserBundle:Permission')->getPermissionsByRole($entity->getRole())
            );
        }

        return $entity;
    }

    /**
     * @return User
     */
    public function getSystemAdministrator()
    {
        $adminRole = $this->em->getRepository('MauticUserBundle:Role')->findOneBy(['isAdmin' => true]);

        return $this->getRepository()->findOneBy(
            [
                'role'        => $adminRole,
                'isPublished' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(['User'], 'Entity must be of class User()');
        }

        switch ($action) {
            case 'pre_save':
                $name = UserEvents::USER_PRE_SAVE;
                break;
            case 'post_save':
                $name = UserEvents::USER_POST_SAVE;
                break;
            case 'pre_delete':
                $name = UserEvents::USER_PRE_DELETE;
                break;
            case 'post_delete':
                $name = UserEvents::USER_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new UserEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = [];
        switch ($type) {
            case 'role':
                $results = $this->em->getRepository(Role::class)->getRoleList($filter, $limit);
                break;
            case 'user':
                $results = $this->em->getRepository(User::class)->getUserList($filter, $limit);
                break;
            case 'position':
                $results = $this->em->getRepository(User::class)->getPositionList($filter, $limit);
                break;
        }

        return $results;
    }

    /**
     * Resets the user password and emails it.
     *
     * @param string $newPassword
     */
    public function resetPassword(User $user, UserPasswordEncoder $encoder, $newPassword)
    {
        $encodedPassword = $this->checkNewPassword($user, $encoder, $newPassword);

        $user->setPassword($encodedPassword);
        $this->saveEntity($user);
    }

    /**
     * @return UserToken
     */
    protected function getResetToken(User $user)
    {
        $userToken = new UserToken();
        $userToken->setUser($user)
            ->setAuthorizator(UserTokenAuthorizator::RESET_PASSWORD_AUTHORIZATOR)
            ->setExpiration((new \DateTime())->add(new \DateInterval('PT24H')))
            ->setOneTimeOnly();

        return $this->userTokenService->generateSecret($userToken, 64);
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function confirmResetToken(User $user, $token)
    {
        $userToken = new UserToken();
        $userToken->setUser($user)
            ->setAuthorizator(UserTokenAuthorizator::RESET_PASSWORD_AUTHORIZATOR)
            ->setSecret($token);

        return $this->userTokenService->verify($userToken);
    }

    /**
     * @throws \RuntimeException
     */
    public function sendResetEmail(User $user)
    {
        $mailer = $this->mailHelper->getMailer();

        $resetToken = $this->getResetToken($user);
        $this->em->persist($resetToken);
        try {
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->addError($exception->getMessage());
            throw new \RuntimeException();
        }
        $resetLink  = $this->router->generate('mautic_user_passwordresetconfirm', ['token' => $resetToken->getSecret()], UrlGeneratorInterface::ABSOLUTE_URL);

        $mailer->setTo([$user->getEmail() => $user->getName()]);
        $mailer->setSubject($this->translator->trans('mautic.user.user.passwordreset.subject'));
        $text = $this->translator->trans(
            'mautic.user.user.passwordreset.email.body',
            ['%name%' => $user->getFirstName(), '%resetlink%' => '<a href="'.$resetLink.'">'.$resetLink.'</a>']
        );
        $text = str_replace('\\n', "\n", $text);
        $html = nl2br($text);

        $mailer->setBody($html);
        $mailer->setPlainText(strip_tags($text));

        $mailer->send();
    }

    /**
     * Set user preference.
     *
     * @param      $key
     * @param null $value
     * @param User $user
     */
    public function setPreference($key, $value = null, User $user = null)
    {
        if (null == $user) {
            $user = $this->userHelper->getUser();
        }

        $preferences       = $user->getPreferences();
        $preferences[$key] = $value;

        $user->setPreferences($preferences);

        $this->getRepository()->saveEntity($user);
    }

    /**
     * Get user preference.
     *
     * @param      $key
     * @param null $default
     * @param User $user
     */
    public function getPreference($key, $default = null, User $user = null)
    {
        if (null == $user) {
            $user = $this->userHelper->getUser();
        }
        $preferences = $user->getPreferences();

        return (isset($preferences[$key])) ? $preferences[$key] : $default;
    }

    /**
     * Return list of Users for formType Choice.
     *
     * @return array
     */
    public function getOwnerListChoices()
    {
        return $this->getRepository()->getOwnerListChoices();
    }
}
