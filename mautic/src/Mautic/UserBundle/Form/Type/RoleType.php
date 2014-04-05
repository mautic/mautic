<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class RoleType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class RoleType extends AbstractType
{
    private $bundles;
    private $container;
    private $em;

    /**
     * @param Container        $container
     * @param array            $bundles
     */
    public function __construct(Container $container, EntityManager $em, array $bundles) {
        $this->container = $container;
        $this->em        = $em;
        $this->bundles   = array_keys($bundles);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
            'label'      => 'mautic.user.role.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.user.role.form.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('isAdmin', 'choice', array(
            'choices'   => array(
                '0'   => 'mautic.core.form.no',
                '1'   => 'mautic.core.form.yes',
            ),
            'expanded'  => true,
            'multiple'  => false,
            'label'     => 'matuic.user.role.form.isadmin',
            'attr'      => array(
                'onclick' => 'Mautic.togglePermissionVisibility();'
            )
        ));

        // add a normal text field, but add your transformer to it
        $hidden = ($options['data']->isAdmin()) ? ' hide' : '';

        //get current permissions saved to the database for this role if applicable
        $permissionsArray = array();
        if ($options['data']->getId()) {
           $repo             = $this->em->getRepository('MauticUserBundle:Permission');
           $permissionsArray = $repo->getPermissionsByRole($options['data'], true);
        }

        $builder->add(
            'permissions', 'permissions', array(
                'label'    => 'mautic.user.role.form.permissions',
                'mapped'   => false, //we'll have to manually build the permissions for persisting
                'required' => false,
                'attr'     => array(
                    'class' => $hidden
                ),
                'permissions'     => $permissionsArray
            )
        );

        $builder->add('save', 'submit', array(
            'label' => 'mautic.core.form.save',
            'attr'  => array('class' => 'btn btn-primary')
        ));

        $builder->add('cancel', 'submit', array(
            'label' => 'mautic.core.form.cancel',
            'attr'  => array('class'   => 'btn btn-danger')
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => 'Mautic\UserBundle\Entity\Role',
            'cascade_validation' => true,
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "role";
    }
}