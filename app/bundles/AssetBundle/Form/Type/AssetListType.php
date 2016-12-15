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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class AssetListType.
 */
class AssetListType extends AbstractType
{
    /**
     * @var array
     */
    private $choices = [];

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $viewOther = $factory->getSecurity()->isGranted('asset:assets:viewother');
        $repo      = $factory->getModel('asset')->getRepository();
        $repo->setCurrentUser($factory->getUser());
        $choices = $repo->getAssetList('', 0, 0, $viewOther);

        foreach ($choices as $asset) {
            $this->choices[$asset['language']][$asset['id']] = $asset['title'];
        }

        //sort by language
        ksort($this->choices);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices'     => $this->choices,
            'empty_value' => false,
            'expanded'    => false,
            'multiple'    => true,
            'required'    => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'asset_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
