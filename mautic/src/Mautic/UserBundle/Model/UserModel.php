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

/**
 * Class UserModel
 * {@inheritdoc}
 * @package Mautic\UserBundle\Model
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
     * @param      $entity
     * @param bool $isNew
     * @return int
     */
    public function saveEntity($entity, $isNew = false)
    {
        if (!$entity instanceof User) {
            //@TODO add error message
            return 0;
        }

        $permissionNeeded = ($isNew) ? "create" : "editother";
        if (!$this->container->get('mautic_core.permissions')->isGranted('user:users:'. $permissionNeeded)) {
            //@TODO add error message
            return 0;
        }

        //check to see if the password needs to be rehashed
        $submittedPassword = $this->request->request->get('user[password][password]', null, true);
        if (!empty($submittedPassword)) {
            //hash the clear password submitted via the form
            $security = $this->container->get('security.encoder_factory');
            $encoder  = $security->getEncoder($entity);
            $password = $encoder->encodePassword($entity->getPassword(), $entity->getSalt());
            $entity->setPassword($password);
        } elseif (!$isNew) {
            //get the original password to save if password is empty from the form
            $originalPassword = ($entity->getId()) ? $entity->getPassword() : '';

            //This is an existing user with a blank password so set the original password
            $entity->setPassword($originalPassword);
        } else {
            //@TODO throw error
            return 0;
        }

        return parent::saveEntity($entity, $isNew);
    }
}