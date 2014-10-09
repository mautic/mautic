<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Security\Permissions;

use Mautic\CategoryBundle\Helper\PermissionHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class PagePermissions
 *
 * @package Mautic\PageBundle\Security\Permissions
 */
class PagePermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addExtendedPermissions('pages');
        $this->addStandardPermissions('categories');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'page';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $this->addStandardFormFields('page', 'categories', $builder, $data);
        $this->addExtendedFormFields('page', 'pages', $builder, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $permissions
     */
    public function analyzePermissions (array &$permissions)
    {
        parent::analyzePermissions($permissions);

        PermissionHelper::analyzePermissions('page', 'pages', $permissions);
    }
}