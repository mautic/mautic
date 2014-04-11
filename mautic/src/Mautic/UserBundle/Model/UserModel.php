<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model
 */
class UserModel extends FormModel
{

    /**
     * @var string
     */
    protected $repository = 'MauticUserBundle:User';
    /**
     * @var string
     */
    protected $permissionBase = 'user:users';

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param bool  $isNew
     * @param array $overrides
     * @return int
     */
    public function saveEntity($entity, $isNew = false, $overrides = array())
    {
        if (!$entity instanceof User) {
            //@TODO add error message
            return 0;
        }

        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic.security')->isGranted('user:users:'. $permissionNeeded)) {
            //@TODO add error message
            return 0;
        }

        return parent::saveEntity($entity, $isNew, $overrides);
    }


    public function checkNewPassword(User $entity, Request $request, $container) {
        if (!$entity instanceof User) {
            //@TODO add error message
            return 0;
        }

        $submittedPassword = $request->request->get('user[plainPassword][password]', null, true);
        if (!empty($submittedPassword)) {
            //hash the clear password submitted via the form
            $security = $container->get('security.encoder_factory');
            $encoder  = $security->getEncoder($entity);
            $password = $encoder->encodePassword($submittedPassword, $entity->getSalt());
        } else {
            //get the original password to save if password is empty from the form
            $originalPassword = $entity->getPassword();
            //This is an existing user with a blank password so set the original password
            $password = $originalPassword;
        }

        return $password;
    }
}