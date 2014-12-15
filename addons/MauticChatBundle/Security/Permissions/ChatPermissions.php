<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class ChatPermissions
 *
 * @package Mautic\CoreBundle\Security\AbstractPermissions
 */
class ChatPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->permissions = array(
            'channels' => array(
                'editother'    => 16,
                'create'       => 32,
                'archiveother' => 512,
                'full'         => 1024
            )
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string|void
     */
    public function getName() {
        return 'chat';
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    {
        $builder->add('chat:channels', 'button_group', array(
            'choices'  => array(
                'editother'    => 'mautic.core.permissions.editother',
                'create'       => 'mautic.core.permissions.create',
                'archiveother' => 'mautic.chat.permissions.archiveother',
                'full'         => 'mautic.core.permissions.full'
            ),
            'label'    => 'mautic.chat.permissions.channels',
            'multiple' => true,
            'attr'     => array(
                'onchange' => 'Mautic.onPermissionChange(this, \'chat\')'
            ),
            'data'     => (!empty($data['channels']) ? $data['channels'] : array())
        ));
    }
}