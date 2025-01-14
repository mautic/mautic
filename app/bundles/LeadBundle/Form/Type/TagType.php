<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Form\DataTransformer\TagEntityModelTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['add_transformer']) {
            $transformer = new TagEntityModelTransformer(
                $this->em,
                Tag::class,
                ($options['multiple'])
            );

            $builder->addModelTransformer($transformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'         => 'mautic.lead.tags',
                'class'         => 'MauticLeadBundle:Tag',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')->orderBy('t.tag', 'ASC');
                },
                'choice_label'    => 'tag',
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
    public function getBlockPrefix()
    {
        return 'lead_tag';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
