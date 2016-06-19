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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\WebhookBundle\Form\DataTransformer\EventsToArrayTransformer;


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
            'name',
            'text',
            array(
                'label'      => 'mautic.core.name',
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

        $events = $options['events'];

        $choices = array();
        foreach ($events as $type => $event)
        {
            $choices[$type] = $event['label'];
        }

        $builder->add('events', 'choice',  array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'label'      => 'mautic.webhook.form.webhook.events',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => ''),
            )
        );

        $builder->get('events')->addModelTransformer(new EventsToArrayTransformer($options['data']));

        $builder->add('buttons', 'form_buttons');

        $builder->add('sendTest', 'button',
            array(
                'attr' => array('class' => 'btn btn-success', 'onclick' => 'Mautic.sendHookTest(this)'),
                'label' => 'mautic.webhook.send.test.payload',

        ));

        //add category
        $builder->add('category', 'category', array(
            'bundle' => 'Webhook'
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

        $resolver->setOptional(array('events'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "webhook";
    }
}