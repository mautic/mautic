<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DynamicContentListType.
 */
class DynamicContentListType extends AbstractType
{
    /**
     * @var DynamicContentRepository
     */
    private $repo;
    private $viewOther;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->viewOther = $factory->getSecurity()->isGranted('dynamicContent:dynamicContents:viewother');
        $this->repo = $factory->getModel('dynamicContent')->getRepository();

        $this->repo->setCurrentUser($factory->getUser());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $viewOther = $this->viewOther;
        $repo = $this->repo;

        $resolver->setDefaults(
            [
                'choices' => function (Options $options) use ($repo, $viewOther) {
                    static $choices;

                    if (is_array($choices)) {
                        return $choices;
                    }

                    $variantParent = (int) (isset($options['variantParent']) ? $options['variantParent'] : 0);
                    $ignoreIds = isset($options['ignore_ids']) ? $options['ignore_ids'] : [];

                    $choices = [];

                    $entities = $repo->getDynamicContentList('', 0, 0, $viewOther, $variantParent, $ignoreIds);
                    foreach ($entities as $entity) {
                        $choices[$entity['language']][$entity['id']] = $entity['name'];
                    }

                    //sort by language
                    ksort($choices);

                    return $choices;
                },
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'empty_value' => function (Options $options) {
                    return (empty($options['choices'])) ? 'mautic.dynamicContent.no.dynamicContent.note' : 'mautic.core.form.chooseone';
                },
                'disabled' => function (Options $options) {
                    return empty($options['choices']);
                },
            ]
        );

        $resolver->setOptional(['ignore_ids', 'variantParent']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dwc_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }
}
