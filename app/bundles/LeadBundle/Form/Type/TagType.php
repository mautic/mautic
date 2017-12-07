<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Form\DataTransformer\TagEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TagType extends AbstractType
{
    /**
     * @var
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['add_transformer']) {
            $transformer = new TagEntityModelTransformer(
                $this->factory->getEntityManager(),
                'MauticLeadBundle:Tag',
                'id',
                ($options['multiple'])
            );

            $builder->addModelTransformer($transformer);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'label'         => 'mautic.lead.tags',
                'class'         => 'MauticLeadBundle:Tag',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.tag', 'ASC');
                },
                'property'        => 'tag',
                'multiple'        => true,
                'required'        => false,
                'disabled'        => false,
                'add_transformer' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_tag';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'entity';
    }
}
