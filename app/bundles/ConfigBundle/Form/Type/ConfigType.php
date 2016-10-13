<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data'] as $config) {
            if (isset($config['formAlias']) && isset($config['parameters'])) {
                $builder->add($config['formAlias'], $config['formAlias'], [
                    'data' => $config['parameters'],
                ]);
            }
        }

        $translator = $this->translator;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $translator) {
            $form = $event->getForm();

            foreach ($form as $config => $configForm) {
                foreach ($configForm as $key => $child) {
                    if (in_array($key, $options['doNotChange'])) {
                        if ($options['doNotChangeDisplayMode'] == 'mask') {
                            $fieldOptions = $child->getConfig()->getOptions();

                            $configForm->add($key, 'text', [
                                'label'    => $fieldOptions['label'],
                                'required' => false,
                                'mapped'   => false,
                                'disabled' => true,
                                'attr'     => [
                                    'placeholder' => $translator->trans('mautic.config.restricted'),
                                    'class'       => 'form-control',
                                ],
                                'label_attr' => ['class' => 'control-label'],
                            ]);
                        } elseif ($options['doNotChangeDisplayMode'] == 'remove') {
                            $configForm->remove($key);
                        }
                    }
                }
            }
        });

        $builder->add('buttons', 'form_buttons',
            [
                'apply_onclick' => 'Mautic.activateBackdrop()',
                'save_onclick'  => 'Mautic.activateBackdrop()',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config';
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired([
            'doNotChange',
            'doNotChangeDisplayMode',
        ]);
    }
}
