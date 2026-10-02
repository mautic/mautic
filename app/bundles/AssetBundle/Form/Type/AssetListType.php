<?php

namespace Mautic\AssetBundle\Form\Type;

use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class AssetListType extends AbstractType
{
    public function __construct(
        private readonly CorePermissions $corePermissions,
        private readonly UserHelper $userHelper,
        private readonly AssetRepository $assetRepository,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices'           => $this->getAssetChoices(),
            'placeholder'       => false,
            'expanded'          => false,
            'multiple'          => true,
            'required'          => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    private function getAssetChoices(): array
    {
        $choices   = [];
        $viewOther = $this->corePermissions->isGranted('asset:assets:viewother');

        $this->assetRepository->setCurrentUser($this->userHelper->getUser());
        $assets = $this->assetRepository->getAssetList('', 0, 0, $viewOther);

        foreach ($assets as $asset) {
            $choices[$asset['language']][$asset['title']] = $asset['id'];
        }

        // sort by language
        ksort($choices);

        return $choices;
    }
}
