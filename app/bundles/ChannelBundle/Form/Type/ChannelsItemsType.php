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
use Mautic\CoreBundle\Form\Type\SortableListType;
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
        foreach ($channels as $channel=>$channelConfig) {
            $builder->add(
                $channel,
                $channelConfig['lookupFormType'],
                [
                    'multiple'    => true,
                    'label'       => $channelConfig['label'],
                ]
            );
        }

        $builder->add(
            'includeUrls',
            SortableListType::class,
            [
                'label'           => 'mautic.page.include.urls',
                'attr'            => [
                    'tooltip' => 'mautic.page.urls.desc',
                ],
                'option_required' => false,
                'with_labels'     => false,
                'required'        => false,
            ]
        );

        $builder->add(
            'excludeUrls',
            SortableListType::class,
            [
                'label'           => 'mautic.page.exclude.urls',
                'attr'            => [
                    'tooltip' => 'mautic.page.urls.desc',
                ],
                'option_required' => false,
                'with_labels'     => false,
                'required'        => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'channels_items';
    }
}
