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
use MauticPlugin\MauticMessengerBundle\Helper\MessengerHelper;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormFieldMessengerCheckboxType.
 */
class FormFieldMessengerCheckboxType extends AbstractType
{

    /**
     * @var MessengerHelper
     */
    protected $messengerHelper;

    /**
     * FormFieldMessengerCheckboxType constructor.
     *
     * @param MessengerHelper $messengerHelper
     */
    public function __construct(MessengerHelper $messengerHelper)
    {
        $this->messengerHelper = $messengerHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'messengerCheckboxPlugin',
            'hidden',
            [
                'data' => $this->messengerHelper->getTemplateContent(),
            ]
        );

    }
    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fuck'] = 'ohaaaa';
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'messenger_checkbox';
    }
}
