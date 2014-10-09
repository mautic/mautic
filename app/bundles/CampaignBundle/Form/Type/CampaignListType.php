<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CampaignListType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class CampaignListType extends AbstractType
{

    private $choices = array();

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $campaigns = $factory->getModel('campaign')->getRepository()->getPublishedCampaigns(null, null, true);

        foreach ($campaigns as $campaign) {
            $this->choices[$campaign['id']] = $campaign['id'] . ':' . $campaign['name'];
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices'       => $this->choices,
            'empty_value'   => false,
            'expanded'      => false,
            'multiple'      => true,
            'required'      => false
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