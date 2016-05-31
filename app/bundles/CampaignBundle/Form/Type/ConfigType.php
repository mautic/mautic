<?php
// plugins/HelloWorldBundle/Form/Type/ConfigType.php
namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'campaign_time_wait_on_event_false',
            'text',
            array(
                'label' => 'mautic.campaignconfig.campaign_time_wait_on_event_false',
                'label_attr'  => array('class' => 'control-label'),
                'data'  => $options['data']['campaign_time_wait_on_event_false'],
                'attr'  => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.campaignconfig.campaign_time_wait_on_event_false_tooltip'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignconfig';
    }
}