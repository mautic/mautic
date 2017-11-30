<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormType.
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
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->security   = $factory->getSecurity();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('form.form', $options));

        //details
        $builder->add('name', 'text', [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('description', 'textarea', [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        //add category
        $builder->add('category', 'category', [
            'bundle' => 'form',
        ]);

        $builder->add('template', 'theme_list', [
            'feature'     => 'form',
            'empty_value' => ' ',
            'attr'        => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.form.template.help',
            ],
        ]);

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

        $builder->add('isPublished', 'yesno_button_group', [
            'read_only' => $readonly,
            'data'      => $data,
        ]);

        $builder->add('inKioskMode', 'yesno_button_group', [
            'label' => 'mautic.form.form.kioskmode',
            'attr'  => [
                'tooltip' => 'mautic.form.form.kioskmode.tooltip',
            ],
        ]);

        // Render style for new form by default
        if ($options['data']->getId() === null) {
            $options['data']->setRenderStyle(true);
        }

        $builder->add('renderStyle', 'yesno_button_group', [
            'label'      => 'mautic.form.form.renderstyle',
            'data'       => ($options['data']->getRenderStyle() === null) ? true : $options['data']->getRenderStyle(),
            'empty_data' => true,
            'attr'       => [
                'tooltip' => 'mautic.form.form.renderstyle.tooltip',
            ],
        ]);

        $builder->add('publishUp', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('publishDown', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('postAction', 'choice', [
            'choices' => [
                'return'   => 'mautic.form.form.postaction.return',
                'redirect' => 'mautic.form.form.postaction.redirect',
                'message'  => 'mautic.form.form.postaction.message',
            ],
            'label'      => 'mautic.form.form.postaction',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'    => 'form-control',
                'onchange' => 'Mautic.onPostSubmitActionChange(this.value);',
            ],
            'required'    => false,
            'empty_value' => false,
        ]);

        $postAction = (isset($options['data'])) ? $options['data']->getPostAction() : '';
        $required   = (in_array($postAction, ['redirect', 'message'])) ? true : false;
        $builder->add('postActionProperty', 'text', [
            'label'      => 'mautic.form.form.postactionproperty',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => $required,
        ]);

        $builder->add('sessionId', 'hidden', [
            'mapped' => false,
        ]);

        $builder->add('buttons', 'form_buttons');
        $builder->add('formType', 'hidden', ['empty_data' => 'standalone']);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => 'Mautic\FormBundle\Entity\Form',
            'validation_groups' => [
                'Mautic\FormBundle\Entity\Form',
                'determineValidationGroups',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mauticform';
    }
}
