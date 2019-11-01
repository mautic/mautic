<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * @param CorePermissions $corePermissions
     * @param AssetModel      $assetModel
     * @param UserHelper      $userHelper
     */
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
        $viewOther = $this->corePermissions->isGranted('asset:assets:viewother');
        $repo      = $this->assetModel->getRepository();
        $repo->setCurrentUser($this->userHelper->getUser());
        $choices = $repo->getAssetList('', 0, 0, $viewOther);

        foreach ($choices as $asset) {
            $choices[$asset['language']][$asset['id']] = $asset['title'];
        }

        //sort by language
        ksort($choices);

        $resolver->setDefaults([
            'choices'     => $choices,
            'empty_value' => false,
            'expanded'    => false,
            'multiple'    => true,
            'required'    => false,
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
}
