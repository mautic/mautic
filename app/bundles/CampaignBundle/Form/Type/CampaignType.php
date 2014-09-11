<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CategoryBundle\Helper\FormHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CampaignType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class CampaignType extends AbstractType
{

    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->security   = $factory->getSecurity();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('campaign', $options));

        $builder->add("campaigns-panel-wrapper-start", 'panel_wrapper_start', array(
            'attr' => array(
                'id' => "campaigns-panel"
            )
        ));

        //details
        $builder->add("details-panel-start", 'panel_start', array(
            'label'      => 'mautic.campaign.form.panel.details',
            'dataParent' => '#campaigns-panel',
            'bodyId'     => 'details-panel',
            'bodyAttr'   => array('class' => 'in')
        ));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.campaign.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.campaign.form.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));


        $data = $options['data']->getType();
        if (empty($data)) {
            $data = 'interval';
        }
        $builder->add('type', 'button_group', array(
            'choices' => array(
                'interval' => 'mautic.campaign.form.type.interval',
                'date'     => 'mautic.campaign.form.type.date'
            ),
            'expanded'    => true,
            'multiple'    => false,
            'label_attr'  => array('class' => 'control-label'),
            'label'       => 'mautic.campaign.form.type',
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'onclick' => 'Mautic.updateCampaignEventLinks();'
            ),
            'data'        => $data
        ));

        //add category
        FormHelper::buildForm($this->translator, $builder);

        $builder->add('triggerExistingLeads', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'    => true,
            'multiple'    => false,
            'label'       => 'mautic.campaign.form.existingleads',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => false
        ));

        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->isGranted('campaign:campaigns:publish');
            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('campaign:campaigns:publish')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = true;
        }

        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'    => true,
            'multiple'    => false,
            'label_attr'  => array('class' => 'control-label'),
            'label'       => 'mautic.campaign.form.ispublished',
            'empty_value' => false,
            'required'    => false,
            'read_only'   => $readonly,
            'data'        => $data
        ));

        $builder->add('publishUp', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add('publishDown', 'datetime', array(
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'data-toggle' => 'datetime'
            ),
            'format'     => 'yyyy-MM-dd HH:mm',
            'required'   => false
        ));

        $builder->add("details-panel-end", 'panel_end');

        //actions
        $builder->add("events-panel-start", 'panel_start', array(
            'label'      => 'mautic.campaign.form.panel.events',
            'dataParent' => '#campaigns-panel',
            'bodyId'     => 'events-panel'
        ));

        $builder->add("events-panel-end", 'panel_end');

        $builder->add("campaigns-panel-wrapper-end", 'panel_wrapper_end');

        $builder->add('tempId', 'hidden', array(
            'mapped' => false
        ));

        $builder->add('buttons', 'form_buttons');

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\CampaignBundle\Entity\Campaign',
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaign";
    }
}