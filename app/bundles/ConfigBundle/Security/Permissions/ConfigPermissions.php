<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class ConfigPermissions
 */
class ConfigPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = array(
            'config' => array(
                'full' => 1024
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'config';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $builder->add('config:config', 'button_group', array(
            'choices'    => array(
                'full' => 'mautic.user.account.permissions.editall',
            ),
            'label'      => 'mautic.user.permissions.profile',
            'attr'       => array(
                'onchange' => 'Mautic.onPermissionChange(this, \'user\')'
            ),
            'data'       => (!empty($data['profile']) ? $data['profile'] : array()),
            'multiple' => true

        ));
    }
}
