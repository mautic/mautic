<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CampaignListType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class CampaignListType extends AbstractType
{

    private $model;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->model = $factory->getModel('campaign');
        $this->thisString = $factory->getTranslator()->trans('mautic.campaign.form.thiscampaign');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $model = $this->model;
        $msg   = $this->thisString;
        $resolver->setDefaults(array(
            'choices'       => function (Options $options) use ($model, $msg) {
                $choices = array();
                $campaigns = $model->getRepository()->getPublishedCampaigns(null, null, true);
                foreach ($campaigns as $campaign) {
                    $choices[$campaign['id']] = $campaign['name'];
                }

                //sort by language
                asort($choices);

                if ($options['include_this']) {
                    $choices = array('this' => $msg) + $choices;
                }

                return $choices;
            },
            'empty_value'   => false,
            'expanded'      => false,
            'multiple'      => true,
            'required'      => false,
            'include_this'  => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaign_list";
    }

    public function getParent()
    {
        return 'choice';
    }
}