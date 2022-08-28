<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                        $choices[$campaign['name']] = $campaign['id'];
                    }

                    //sort by language
                    ksort($choices);

                    if ($options['include_this']) {
                        $choices = [$options['this_translation'] => 'this'] + $choices;
                    }

                    return $choices;
                },
                'placeholder'       => false,
                'expanded'          => false,
                'multiple'          => true,
                'required'          => false,
                'include_this'      => false,
                'this_translation'  => 'mautic.campaign.form.thiscampaign',
            ]
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'campaign_list';
    }
}
