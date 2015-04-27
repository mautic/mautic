<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Event\StatusChangeEvent;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class UserModel
 */
class UserModel extends FormModel
{
    /**
     * Define statuses that are supported
     *
     * @var array
     */
    private $supportedOnlineStatuses = array(
        'online',
        'idle',
        'away',
        'manualaway',
        'dnd',
        'offline'
    );

    /**
     * {@inheritdoc}
     */
    public function getRepository ()
    {
        return $this->em->getRepository('MauticUserBundle:User');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase ()
    {
        return 'user:users';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function saveEntity ($entity, $unlock = true)
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(array('User'), 'Entity must be of class User()');
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * Checks for a new password and rehashes if necessary
     *
     * @param User                     $entity
     * @param PasswordEncoderInterface $encoder
     * @param string                   $submittedPassword
     *
     * @return string
     */
    public function checkNewPassword (User $entity, PasswordEncoderInterface $encoder, $submittedPassword)
    {
        if (!empty($submittedPassword)) {
            //hash the clear password submitted via the form
            return $encoder->encodePassword($submittedPassword, $entity->getSalt());
        }

        return $entity->getPassword();
    }


    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm ($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(array('User'), 'Entity must be of class User()');
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('user', $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity ($id = null)
    {
        if ($id === null) {
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
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent ($action, &$entity, $isNew = false, $event = false)
    {
        if (!$entity instanceof User) {
            throw new MethodNotAllowedHttpException(array('User'), 'Entity must be of class User()');
        }

        switch ($action) {
            case "pre_save":
                $name = UserEvents::USER_PRE_SAVE;
                break;
            case "post_save":
                $name = UserEvents::USER_POST_SAVE;
                break;
            case "pre_delete":
                $name = UserEvents::USER_PRE_DELETE;
                break;
            case "post_delete":
                $name = UserEvents::USER_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new UserEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return false;
    }

    /**
     * Get list of entities for autopopulate fields
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults ($type, $filter = '', $limit = 10)
    {
        $results = array();
        switch ($type) {
            case 'role':
                $results = $this->em->getRepository('MauticUserBundle:Role')->getRoleList($filter, $limit);
                break;
            case 'user':
                $results = $this->em->getRepository('MauticUserBundle:User')->getUserList($filter, $limit);
                break;
            case 'position':
                $results = $this->em->getRepository('MauticUserBundle:User')->getPositionList($filter, $limit);
                break;
        }

        return $results;
    }

    /**
     * Resets the user password and emails it
     *
     * @param User $user
     */
    public function resetPassword (User $user, PasswordEncoderInterface $encoder)
    {
        $newPassword     = hash('sha1', uniqid(mt_rand()));
        $encodedPassword = $this->checkNewPassword($user, $encoder, $newPassword);

        $user->setPassword($encodedPassword);
        $this->saveEntity($user);

        // Email the user
        $mailer = $this->factory->getMailer();

        $mailer->setTo(array($user->getEmail() => $user->getName()));
        $mailer->setSubject($this->translator->trans('mautic.user.user.passwordreset.subject'));
        $body = $this->translator->trans('mautic.user.user.passwordreset.body', array('%name%' => $user->getFirstName(), '%password%' => $newPassword));
        $body = str_replace('\\n', "\n", $body);
        $mailer->setBody($body);

        //queue the message
        $mailer->send();
    }

    /**
     * Set user preference
     *
     * @param      $key
     * @param null $value
     * @param User $user
     */
    public function setPreference ($key, $value = null, User $user = null)
    {
        if ($user == null) {
            $user = $this->factory->getUser();
        }

        $preferences       = $user->getPreferences();
        $preferences[$key] = $value;

        $user->setPreferences($preferences);

        $this->getRepository()->saveEntity($user);
    }

    /**
     * Get user preference
     *
     * @param      $key
     * @param null $default
     * @param User $user
     *
     * @return null
     */
    public function getPreference ($key, $default = null, User $user = null)
    {
        if ($user == null) {
            $user = $this->factory->getUser();
        }
        $preferences = $user->getPreferences();

        return (isset($preferences[$key])) ? $preferences[$key] : $default;
    }

    /**
     * @param $status
     */
    public function setOnlineStatus($status)
    {
        $status = strtolower($status);

        if (in_array($status, $this->supportedOnlineStatuses)) {
            $user = $this->factory->getUser();
            $user->setOnlineStatus($status);
            $this->getRepository()->saveEntity($user);

            $dispatcher = $this->factory->getDispatcher();
            if ($dispatcher->hasListeners(UserEvents::STATUS_CHANGE)) {
                $event = new StatusChangeEvent($this->factory);
                $dispatcher->dispatch(UserEvents::STATUS_CHANGE, $event);
            }
        }
    }
}