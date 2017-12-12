<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use MauticPlugin\MauticMessengerBundle\Model\MessengerMessageModel;

/**
 * Class MessengerMessageListType.
 */
class MessengerMessageListType extends AbstractType
{
    /**
     * @var MessengerMessageModel $messengerMessageModel
     */
    protected $messengerMessageModel;

    /**
     * MessengerMessageType constructor.
     *
     * @param MessengerMessageModel $messengerMessageModel
     */
    public function __construct(MessengerMessageModel $messengerMessageModel)
    {
        $this->messengerMessageModel = $messengerMessageModel;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \MauticPlugin\MauticMessengerBundle\Entity\MessengerMessageRepository $repo */
        $repo =  $this->messengerMessageModel->getRepository();
        $builder->add(
            'leadFieldAddress1',
            'choice',
            [
                'choices' => [],
                'label' => 'mautic.form.field.form.lead_field',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'mautic.form.field.help.lead_field',
                ],
                'required' => false,
            ]
        );

    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'messenger_messages_list';
    }
}
