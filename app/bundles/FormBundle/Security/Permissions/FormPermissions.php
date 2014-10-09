<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Security\Permissions;

use Mautic\CategoryBundle\Helper\PermissionHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class FormPermissions
 *
 * @package Mautic\FormBundle\Security\Permissions
 */
class FormPermissions extends AbstractPermissions
{

    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('forms');
        $this->addStandardPermissions('categories');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName ()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @param array                $data
     */
    public function buildForm (FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('form', 'categories', $builder, $data);
        $this->addExtendedFormFields('form', 'forms', $builder, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        //analyze category permissions
        PermissionHelper::analyzePermissions('form', 'forms', $permissions);
    }
}