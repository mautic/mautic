<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class CampaignListType extends AbstractType
{
    /**
     * @var bool
     */
    private $canViewOther = false;

    public function __construct(
        private CampaignModel $model,
        protected TranslatorInterface $translator,
        CorePermissions $security
    ) {
        $this->canViewOther = $security->isGranted('campaign:campaigns:viewother');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'      => function (Options $options): array {
                    $choices   = [];
                    $campaigns = $this->model->getRepository()->getPublishedCampaigns(null, null, true, $this->canViewOther);
                    foreach ($campaigns as $campaign) {
                        $choices[$campaign['name']] = $campaign['id'];
                    }

                    // sort by language
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
}
