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
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\UserBundle\Form\DataTransformer\RoleToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class UserType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class UserType extends AbstractType
{

    private $container;
    private $securityContext;
    private $em;

    /**
     * @param Container       $container
     * @param SecurityContext $securityContext
     * @param EntityManager   $em
     */
    public function __construct(Container $container, SecurityContext $securityContext, EntityManager $em) {
        $this->container       = $container;
        $this->securityContext = $securityContext;
        $this->em              = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('username', 'text', array(
            'label'      => 'mautic.user.user.form.username',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('firstName', 'text', array(
            'label'      => 'mautic.user.user.form.firstname',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('lastName',  'text', array(
            'label'      => 'mautic.user.user.form.lastname',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('position',  'text', array(
            'label'      => 'mautic.user.user.form.position',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('email', 'email', array(
            'label'      => 'mautic.user.user.form.email',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control',
                'preaddon' => 'fa fa-envelope'
            )
        ));

        $builder->add('role_lookup', 'text', array(
            'label'      => 'mautic.user.user.form.role',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.core.help.autocomplete',
            ),
            'mapped'     => false
        ));

        $roleTransformer  = new RoleToIdTransformer($this->em);
        $builder->add(
            $builder->create('role', 'hidden', array(
                'required' => true,
            ))
            ->addViewTransformer($roleTransformer)
        );

        $existing = (!empty($options['data']) && $options['data']->getId());
        $placeholder = ($existing) ?
            $this->container->get('translator')->trans('mautic.user.user.form.passwordplaceholder') : '';
        $required = ($existing) ? false : true;
        $builder->add('plainPassword', 'repeated', array(
            'first_name'        => 'password',
            'first_options'     => array(
                'label'      => 'mautic.user.user.form.password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'placeholder' => $placeholder,
                    'tooltip'     => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'    => 'fa fa-lock'
                ),
                'required'   => $required
            ),
            'second_name'       => 'confirm',
            'second_options'    => array(
                'label'      => 'mautic.user.user.form.passwordconfirm',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'       => 'form-control',
                    'placeholder' => $placeholder,
                    'tooltip'     => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'    => 'fa fa-lock'
                ),
                'required'   => $required
            ),
            'type'              => 'password',
            'invalid_message'   => 'mautic.user.user.password.mismatch',
            'required'          => $required
        ));

        $builder->add('isActive', 'choice', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.core.form.isactive',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('save', 'submit', array(
            'label' => 'mautic.core.form.save',
            'attr'  => array(
                'class' => 'btn btn-primary',
                'icon'  => 'fa fa-check padding-sm-right'
            ),
        ));

        $builder->add('cancel', 'submit', array(
            'label' => 'mautic.core.form.cancel',
            'attr'  => array(
                'class'   => 'btn btn-danger',
                'icon'    => 'fa fa-times padding-sm-right'
            )
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