<?php

namespace Mautic\AssetBundle\Form\Type;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetListType extends AbstractType
{
    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @var AssetModel
     */
    private $assetModel;

    /**
     * @var UserHelper
     */
    private $userHelper;

    public function __construct(
        CorePermissions $corePermissions,
        AssetModel $assetModel,
        UserHelper $userHelper
    ) {
        $this->corePermissions = $corePermissions;
        $this->assetModel      = $assetModel;
        $this->userHelper      = $userHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices'           => $this->getAssetChoices(),
            'placeholder'       => false,
            'expanded'          => false,
            'multiple'          => true,
            'required'          => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'asset_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return array
     */
    private function getAssetChoices()
    {
        $choices   = [];
        $viewOther = $this->corePermissions->isGranted('asset:assets:viewother');
        $repo      = $this->assetModel->getRepository();
        $repo->setCurrentUser($this->userHelper->getUser());
        $assets = $repo->getAssetList('', 0, 0, $viewOther);

        foreach ($assets as $asset) {
            $choices[$asset['language']][$asset['title']] = $asset['id'];
        }

        //sort by language
        ksort($choices);

        return $choices;
    }
}
