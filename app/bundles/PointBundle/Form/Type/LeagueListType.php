<?php

namespace Mautic\PointBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\PointBundle\Entity\LeagueRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeagueListType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LeagueRepository
     */
    private $repo;

    public function __construct(EntityManager $em, LeagueRepository $repo)
    {
        $this->em         = $em;
        $this->repo       = $repo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['return_entity']) {
            $transformer = new IdToEntityModelTransformer($this->em, 'MauticPointBundle:League', 'id');
            $builder->addModelTransformer($transformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $leagues = $this->repo->getEntities();
                $choices = [];
                foreach ($leagues as $l) {
                    $choices[$l->getName()] = $l->getId();
                }

                return $choices;
            },
            'label'             => 'mautic.point.league.form.league',
            'label_attr'        => ['class' => 'control-label'],
            'multiple'          => false,
            'required'          => false,
            'return_entity'     => true,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'league';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
