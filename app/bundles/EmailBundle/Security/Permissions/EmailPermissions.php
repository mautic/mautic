<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class EmailPermissions
 *
 * @package Mautic\EmailBundle\Security\Permissions
 */
class EmailPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('categories');
        $this->addExtendedPermissions('emails');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'email';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('email', 'categories', $builder, $data);
        $this->addExtendedFormFields('email', 'emails', $builder, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        $emailPermissions = (isset($permissions['email:emails'])) ? $permissions['email:emails'] : array();
        $catPermissions  = (isset($permissions['email:categories'])) ? $permissions['email:categories'] : array();
        //make sure the user has access to view categories if they have access to view emails
        if ((isset($emailPermissions['viewown']) || isset($emailPermissions['viewother']))
            && !isset($catPermissions['view'])) {
            $permissions['email:categories'][] = 'view';
        }
    }
}