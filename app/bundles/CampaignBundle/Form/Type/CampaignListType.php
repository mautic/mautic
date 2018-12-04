<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CampaignListType.
 */
class CampaignListType extends AbstractType
{
    /**
     * @var CampaignModel
     */
    private $model;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var bool
     */
    private $canViewOther = false;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(CampaignModel $campaignModel, TranslatorInterface $translator, CorePermissions $security)
    {
        $this->model        = $campaignModel;
        $this->translator   = $translator;
        $this->canViewOther = $security->isGranted('campaign:campaigns:viewother');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'      => function (Options $options) {
                    $choices   = [];
                    $campaigns = $this->model->getRepository()->getPublishedCampaigns(null, null, true, $this->canViewOther);
                    foreach ($campaigns as $campaign) {
                        $choices[$campaign['id']] = $campaign['name'];
                    }

                    //sort by language
                    asort($choices);

                    if ($options['include_this']) {
                        $choices = ['this' => $options['this_translation']] + $choices;
                    }

                    return $choices;
                },
                'empty_value'      => false,
                'expanded'         => false,
                'multiple'         => true,
                'required'         => false,
                'include_this'     => false,
                'this_translation' => 'mautic.campaign.form.thiscampaign',
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaign_list';
    }

    public function getParent()
    {
        return 'choice';
    }
}
