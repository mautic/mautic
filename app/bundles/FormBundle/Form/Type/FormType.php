<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CategoryBundle\Helper\FormHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class FormType extends AbstractType
{

    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->security   = $factory->getSecurity();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('form.form', $options));

        $builder->add("forms-panel-wrapper-start", 'panel_wrapper_start', array(
            'attr' => array(
                'id' => "forms-panel"
            )
        ));

        //details
        $builder->add("details-panel-start", 'panel_start', array(
            'label'      => 'mautic.form.form.panel.details',
            'dataParent' => '#forms-panel',
            'bodyId'     => 'details-panel',
            'bodyAttr'   => array('class' => 'in')
        ));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.form.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.form.form.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        //add category
        FormHelper::buildForm($this->translator, $builder);


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

        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.form.form.ispublished',
            'empty_value'   => false,
            'required'      => false,
            'read_only'     => $readonly,
            'data'          => $data
        ));

        $builder->add('publishUp', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'  => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'  => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('postAction', 'choice', array(
            'choices' => array(
                'return'   => 'mautic.form.form.postaction.return',
                'redirect' => 'mautic.form.form.postaction.redirect',
                'message'  => 'mautic.form.form.postaction.message'
            ),
            'label'      => 'mautic.form.form.postaction',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control',
                'onchange' => 'Mautic.onPostSubmitActionChange(this.value);'
            ),
            'required' => false,
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

        $builder->add("details-panel-end", 'panel_end');

        //fields
        $builder->add("fields-panel-start", 'panel_start', array(
            'label' => 'mautic.form.form.panel.fields',
            'dataParent' => '#forms-panel',
            'bodyId'     => 'fields-panel'
        ));

        $builder->add("fields-panel-end", 'panel_end');

        //submit actions
        $builder->add("actions-panel-start", 'panel_start', array(
            'label' => 'mautic.form.form.panel.actions',
            'dataParent' => '#forms-panel',
            'bodyId'     => 'actions-panel'
        ));

        $builder->add("actions-panel-end", 'panel_end');

        $builder->add("forms-panel-wrapper-end", 'panel_wrapper_end');

        $builder->add('tempId', 'hidden', array(
            'mapped' => false
        ));

        $builder->add('buttons', 'form_buttons');

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
            'data_class' => 'Mautic\FormBundle\Entity\Form',
            'validation_groups' => array(
                'Mautic\FormBundle\Entity\Form',
                'determineValidationGroups',
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "mauticform";
    }
}