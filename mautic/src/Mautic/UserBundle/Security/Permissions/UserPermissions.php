<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\CommonPermissions;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;

/**
 * Class UserPermissions
 *
 * @package Mautic\UserBundle\Security\Permissions
 */
class UserPermissions extends CommonPermissions
{

    /**
     * {@inheritdoc}
     *
     * @param Container     $container
     * @param EntityManager $em
     */
    public function __construct(Container $container, EntityManager $em)
    {
        parent::__construct($container, $em);
        $this->permissions = array(
            'users' => array(
                'view'          => 1,
                'editother'     => 4,
                'create'        => 8,
                'deleteother'   => 32,
                'full'          => 1024
            ),
            'roles' => array(
                'view'          => 1,
                'editother'     => 4,
                'create'        => 8,
                'deleteother'   => 32,
                'full'          => 1024
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'user';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options)
    {
        //convert the permission bits from the db into readable names
        $data = $this->convertBitsToPermissionNames($options['permissions']);

        $builder->add('user:users', 'choice', array(
            'choices'      => array(
                'view'          => 'mautic.core.permissions.view',
                'editother'     => 'mautic.core.permissions.edit',
                'create'        => 'mautic.core.permissions.create',
                'deleteother'   => 'mautic.core.permissions.delete',
                'full'          => 'mautic.core.permissions.full'
            ),
            'label'     => 'mautic.user.permissions.users',
            'expanded'  => true,
            'multiple'  => true,
            'attr'      => array(
                'onclick' => 'Mautic.toggleFullPermissions(this, event)'
            ),
            'data'      => (!empty($data['users']) ? $data['users'] : array())
        ));

        $builder->add('user:roles', 'choice', array(
            'choices'           => array(
                'view'          => 'mautic.core.permissions.view',
                'editother'     => 'mautic.core.permissions.edit',
                'create'        => 'mautic.core.permissions.create',
                'deleteother'   => 'mautic.core.permissions.delete',
                'full'          => 'mautic.core.permissions.full'
            ),
            'label'     => 'mautic.user.permissions.roles',
            'expanded'  => true,
            'multiple'  => true,
            'attr'      => array(
                'onclick' => 'Mautic.toggleFullPermissions(this, event)'
            ),
            'data'      => (!empty($data['roles']) ? $data['roles'] : array())
        ));
    }
}