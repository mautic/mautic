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

        $builder->add(
            'events',
            'choice',
            array(
                'label'      => 'mautic.webhook.form.webhook_url',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true,
                //'data'       => $options['events'],
            )
        );

        $builder->add('buttons', 'form_buttons');

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
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "webhook";
    }
}