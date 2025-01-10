<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Form\DataTransformer\TagEntityModelTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Tag>
 */
class TagType extends AbstractType
{
    public function __construct(
        private EntityManager $em,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['add_transformer']) {
            $transformer = new TagEntityModelTransformer(
                $this->em,
                Tag::class,
                $options['multiple']
            );

            $builder->addModelTransformer($transformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'label'           => 'mautic.lead.tags',
                'class'           => Tag::class,
                'query_builder'   => fn (EntityRepository $er) => $er->createQueryBuilder('t')->orderBy('t.tag', Order::Ascending->value),
                'choice_label'    => 'tag',
                'multiple'        => true,
                'required'        => false,
                'disabled'        => false,
                'add_transformer' => false,
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'lead_tag';
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
