<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RoleType
 */
class RoleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('user.role', $options));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.core.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $builder->add('isAdmin', 'yesno_button_group', array(
            'label'       => 'mautic.user.role.form.isadmin',
            'attr'        => array(
                'onchange' => 'Mautic.togglePermissionVisibility();',
                'tooltip'  => 'mautic.user.role.form.isadmin.tooltip'
            )
        ));

        // add a normal text field, but add your transformer to it
        $hidden = ($options['data']->isAdmin()) ? ' hide' : '';

        $builder->add(
            'permissions', 'permissions', array(
                'label'             => 'mautic.user.role.permissions',
                'mapped'            => false, //we'll have to manually build the permissions for persisting
                'required'          => false,
                'attr'              => array(
                    'class' => $hidden
                ),
                'permissionsConfig' => $options['permissionsConfig']
            )
        );

        $builder->add('buttons', 'form_buttons');

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => 'Mautic\UserBundle\Entity\Role',
            'cascade_validation' => true,
            'permissionsConfig'  => array()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return "role";
    }
}
