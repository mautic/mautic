<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use MauticPlugin\MauticMessengerBundle\Model\MessengerMessageModel;


/**
 * Class FormFieldMessengerCheckboxType.
 */
class SendToMessengerType extends AbstractType
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
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var \MauticPlugin\MauticMessengerBundle\Entity\MessengerMessageRepository $repo */
        $lists =  $this->messengerMessageModel->getRepository()->getMessangerMessagesList();
        $choices = [];
        foreach ($lists as $l) {
            $choices[$l['id']] = $l['name'];
        }
        $builder->add(
            'messages',
            'choice',
            [
                'choices' => $choices,
                'label' => 'mautic.messengerMessage.form.content.choose',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'messenger_send_to_messenger';
    }
}
