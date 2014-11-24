<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CampaignEventAddRemoveLeadType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class CampaignEventAddRemoveLeadType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('addTo', 'campaign_list', array(
            'label'      => 'mautic.campaign.form.addtocampaigns',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control'
            ),
            'required'   => false
        ));

        $builder->add('removeFrom', 'campaign_list', array(
            'label'      => 'mautic.campaign.form.removefromcampaigns',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control'
            ),
            'required'   => false,
            'include_this' => true
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaignevent_addremovelead";
    }
}