<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticSocialBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class SocialPermissions
 *
 * @package Mautic\LeadBundle\Security\Permissions
 */
class SocialPermissions extends AbstractPermissions
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
        return 'social';
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
        $builder->add('social:config', 'button_group', array(
            'choices'  => array(
                'full' => 'mautic.core.permissions.manage'
            ),
            'label'    => 'mautic.social.permissions.config',
            'expanded' => true,
            'multiple' => true,
            'attr'     => array(
                'onclick' => 'Mautic.onPermissionChange(this, event, \'social\')'
            ),
            'data'     => (!empty($data['config']) ? $data['config'] : array())
        ));
    }
}