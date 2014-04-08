<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Form\DataTransformer\CleanTransformer;

/**
 * Class UserType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class UserType extends AbstractType
{

    private $bundles;
    private $container;

    /**
     * @param Container        $container
     * @param array            $bundles
     */
    public function __construct(Container $container, array $bundles) {
        $this->container = $container;
        $this->bundles   = array_keys($bundles);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $transformer = new CleanTransformer();
        $builder->add(
            $builder->create('username', 'text', array(
                'label'      => 'mautic.user.user.form.username',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ))->addViewTransformer($transformer)
        );

        $builder->add(
            $builder->create('firstName', 'text', array(
                'label'      => 'mautic.user.user.form.firstname',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ))->addViewTransformer($transformer)
        );

        $builder->add(
            $builder->create('lastName',  'text', array(
                'label'      => 'mautic.user.user.form.lastname',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ))->addViewTransformer($transformer)
        );

        $builder->add('email', 'email', array(
            'label'      => 'mautic.user.user.form.email',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('role', 'entity', array(
            'label'         => 'mautic.user.user.form.role',
            'label_attr'    => array('class' => 'control-label'),
            'attr'          => array('class' => 'form-control'),
            'class'         => 'MauticUserBundle:Role',
            'property'      => 'name',
            'empty_value'   => 'mautic.core.form.chooseone',
            'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('r')
                    ->orderBy('r.name', 'ASC');
            },
        ));

        $builder->add('password', 'repeated', array(
            'first_name'        => 'password',
            'first_options'     => array(
                'label'      => 'mautic.user.user.form.password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ),
            'second_name'       => 'confirm',
            'second_options'    => array(
                'label'      => 'mautic.user.user.form.passwordconfirm',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ),
            'type'              => 'password',
            'invalid_message'   => 'mautic.user.user.password.mismatch'
        ));

        $builder->add('save', 'submit', array(
            'label' => 'mautic.core.form.save',
            'attr'  => array('class' => 'btn btn-primary'),
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
            'data_class' => 'Mautic\UserBundle\Entity\User',
            'validation_groups' => array(
                'Mautic\UserBundle\Entity\User',
                'determineValidationGroups',
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "user";
    }
}