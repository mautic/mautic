<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Form\Type;

use Mautic\ChannelBundle\Model\MessageModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ChannelsItemsType.
 */
class ChannelsItemsType extends AbstractType
{
    /**
     * @var MessageModel
     */
    private $messageModel;

    /**
     * ChannelsItemsType constructor.
     */
    public function __construct(MessageModel $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $channels = $this->messageModel->getChannels();

        $builder->add(
            'channel',
            ChoiceType::class,
            [
                'label'       => 'mautic.core.channel',
                'choices'     => array_combine(array_keys($channels), array_column($channels, 'label')),
                'attr'        => [
                    'onchange' => 'Mautic.reloadChannelItems(this.value)',
                ],
                'placeholder' => '',
                'constraints' => new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
            ]
        );

        $func = function (FormEvent $e) use ($channels) {
            $data    = $e->getData();
            $form    = $e->getForm();
            if (!empty($data['channel']) && !empty($channels[$data['channel']])) {
                $channelConfig =  $channels[$data['channel']];
                if (isset($channelConfig['lookupFormType'])) {
                    $form->add(
                        'channelId',
                        $channelConfig['lookupFormType'],
                        [
                            'multiple'    => false,
                            'label'       => $channelConfig['label'],
                            'constraints' => new NotBlank(
                                    [
                                        'message' => 'mautic.core.value.required',
                                    ]
                                ),
                        ]
                    );
                }
            }
        };

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'channels_items';
    }
}
