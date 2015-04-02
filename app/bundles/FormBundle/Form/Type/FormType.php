<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormType
 */
class FormType extends AbstractType
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $security;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->security   = $factory->getSecurity();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('form.form', $options));

        //details
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

        //add category
        $builder->add('category', 'category', array(
            'bundle' => 'form'
        ));

        $builder->add('template', 'theme_list', array(
            'feature'     => 'form',
            'empty_value' => ' ',
            'attr'        => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.form.template.help'
            )
        ));

        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->hasEntityAccess(
                'form:forms:publishown',
                'form:forms:publishother',
                $options['data']->getCreatedBy()
            );

            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('form:forms:publishown')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = true;
        }

        $builder->add('isPublished', 'yesno_button_group', array(
            'read_only' => $readonly,
            'data'      => $data
        ));

        $builder->add('inKioskMode', 'yesno_button_group', array(
            'label'       => 'mautic.form.form.kioskmode',
            'attr'        => array(
                'tooltip' => 'mautic.form.form.kioskmode.tooltip'
            )
        ));

        $builder->add('publishUp', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('postAction', 'choice', array(
            'choices'     => array(
                'return'   => 'mautic.form.form.postaction.return',
                'redirect' => 'mautic.form.form.postaction.redirect',
                'message'  => 'mautic.form.form.postaction.message'
            ),
            'label'       => 'mautic.form.form.postaction',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class'    => 'form-control',
                'onchange' => 'Mautic.onPostSubmitActionChange(this.value);'
            ),
            'required'    => false,
            'empty_value' => false
        ));

        $postAction = (isset($options['data'])) ? $options['data']->getPostAction() : '';
        $required   = (in_array($postAction, array('redirect', 'message'))) ? true : false;
        $builder->add('postActionProperty', 'text', array(
            'label'      => 'mautic.form.form.postactionproperty',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => $required
        ));

        $builder->add('sessionId', 'hidden', array(
            'mapped' => false
        ));

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'        => 'Mautic\FormBundle\Entity\Form',
            'validation_groups' => array(
                'Mautic\FormBundle\Entity\Form',
                'determineValidationGroups',
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return "mauticform";
    }
}