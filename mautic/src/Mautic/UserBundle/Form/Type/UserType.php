<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class UserType extends AbstractType
{

    private $translator;
    private $supportedLanguages;
    private $em;

    /**
     * @param TranslatorInterface    $translator
     * @param array         $supportedLanguages
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator         = $factory->getTranslator();
        $this->supportedLanguages = $factory->getParam('supported_languages');
        $this->em                 = $factory->getEntityManager();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        if (!$options['ignore_formexit']) {
            $builder->addEventSubscriber(new FormExitSubscriber($this->translator->trans(
                'mautic.core.form.inform'
            )));
        }

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

        $builder->add('role', 'hidden_entity', array(
            'required'   => true,
            'repository' => 'MauticUserBundle:Role',
            'em'         => $this->em,
            'error_bubbling' => false,
        ));

        $existing = (!empty($options['data']) && $options['data']->getId());
        $placeholder = ($existing) ?
            $this->translator->trans('mautic.user.user.form.passwordplaceholder') : '';
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
                'required'   => $required,
                'error_bubbling'    => false
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
                'required'   => $required,
                'error_bubbling'    => false
            ),
            'type'              => 'password',
            'invalid_message'   => 'mautic.user.user.password.mismatch',
            'required'          => $required,
            'error_bubbling'    => false
        ));

        $builder->add('timezone', 'timezone', array(
            'label'       => 'mautic.user.user.form.timezone',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'   => 'form-control'
            ),
            'multiple'    => false,
            'empty_value' => 'mautic.user.user.form.defaulttimezone'
        ));

        $builder->add('locale', 'choice', array(
            'choices'     => $this->supportedLanguages,
            'label'       => 'mautic.user.user.form.locale',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'   => 'form-control'
            ),
            'multiple'    => false,
            'empty_value' => 'mautic.user.user.form.defaultlocale'
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

        if (empty($options['ignore_formexit'])) {
            $builder->add('buttons', 'form_buttons');
        }

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
            ),
            'ignore_formexit' => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "user";
    }
}