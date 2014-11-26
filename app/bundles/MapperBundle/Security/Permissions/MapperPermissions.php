<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class MapperPermissions
 *
 * @package Mautic\MapperBundle\Security\Permissions
 */
class MapperPermissions extends AbstractPermissions
{

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
     *
     * @return string|void
     */
    public function getName ()
    {
        return 'mapper';
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
        $builder->add('mapper:config', 'button_group', array(
            'choices'  => array(
                'full'         => 'mautic.core.permissions.manage'
            ),
            'label'    => 'mautic.mapper.permissions.config',
            'multiple' => true,
            'attr'     => array(
                'onchange' => 'Mautic.onPermissionChange(this, \'mapper\')'
            ),
            'data'     => (!empty($data['config']) ? $data['config'] : array())
        ));

    }
}
