<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Form\Type;

use Mautic\ChannelBundle\Entity\Channel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChannelType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formModifier = function (FormEvent $event) use ($options) {
            $form = $event->getForm();

            /** @var Channel $channel */
            $data = $event->getData();
            if (is_array($data)) {
                $channelName = $data['channel'];
                $enabled     = $data['isEnabled'];
            } elseif ($data instanceof Channel) {
                $channelName = $data->getChannel();
                $enabled     = $data->isEnabled();
            } else {
                $channelName = $data;
                $enabled     = false;
            }

            if (!$data || !$channelName || !isset($options['channels'][$channelName])) {
                return;
            }

            $channelConfig = $options['channels'][$channelName];
            $form->add(
                'channel',
                HiddenType::class
            );

            $form->add(
                'isEnabled',
                'yesno_button_group',
                [
                    'label' => 'mautic.channel.message.form.enabled',
                    'attr'  => [
                        'onchange' => 'Mautic.toggleChannelFormDisplay(this, \''.$channelName.'\')',
                    ],
                ]
            );

            if (isset($channelConfig['lookupFormType'])) {
                $form->add(
                    'channelId',
                    $channelConfig['lookupFormType'],
                    [
                        'multiple'    => false,
                        'label'       => 'mautic.channel.message.form.message',
                        'constraints' => ($enabled) ? [
                             new NotBlank(
                                 [
                                    'message' => 'mautic.core.value.required',
                                 ]
                             ),
                        ] : [],
                    ]
                );
            }

            if (isset($channelConfig['propertiesFormType'])) {
                $propertiesOptions = [
                    'label' => false,
                ];
                if (!$enabled) {
                    // Disable validation
                    $propertiesOptions['validation_groups'] = false;
                }

                $form->add(
                    'properties',
                    $channelConfig['propertiesFormType'],
                    $propertiesOptions
                );
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $formModifier);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $formModifier);
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['channels'] = $options['channels'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['channels']);
        $resolver->setDefaults(
            [
                'data_class' => Channel::class,
                'label'      => false,
            ]
        );
    }
}
