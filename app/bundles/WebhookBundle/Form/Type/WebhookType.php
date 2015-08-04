<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;


/**
 * Class ReportType
 */
class WebhookType extends AbstractType
{
    /**
     * Factory object
     *
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    private $factory;

    /**
     * Translator object
     *
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'title',
            'text',
            array(
                'label'      => 'mautic.core.title',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true
            )
        );

        $builder->add(
            'description',
            'textarea',
            array(
                'label'      => 'mautic.webhook.form.description',
                'required'   => false,
                'attr'       => array(
                    'class'  => 'form-control',
                )
            )
        );

        $builder->add(
            'webhook_url',
            'text',
            array(
                'label'      => 'mautic.webhook.form.webhook_url',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true
            )
        );

        $builder->add('events', 'choice', array(
                'label'      => 'mautic.webhook.form.webhook_url',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true,
                'choices'     => $options['eventList'], // passed from the controller
                'empty_value' => 'mautic.core.form.chooseone'
            )
        );

        $builder->add('buttons', 'form_buttons');

        // if we have a network type value add in the form
        /*
        if (! empty($options['eventList'])) {

            // get the values from the entity function
            $events = $options['data']->getEvents();

            $builder->add('events', $options['eventList'],
                array (
                    'label' => false,
                    'data'  => $events
                )
            );
        }*/

        //add category
        $builder->add('category', 'category', array(
            'bundle' => 'addon:WebhookBundle'
        ));

        $builder->add('isPublished', 'yesno_button_group');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\WebhookBundle\Entity\Webhook',
        ));

        // allow network types to be sent through - list
        $resolver->setRequired(array('eventList'));

        // allow the specific network type - single
        //$resolver->setOptional(array('event'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "webhook";
    }
}