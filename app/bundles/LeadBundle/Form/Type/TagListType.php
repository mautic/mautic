<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 8/17/15
 * Time: 14:51
 */

namespace Mautic\LeadBundle\Form\Type;


use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TagListType extends AbstractType
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

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'id',
            'hidden'
        );

        $builder->add(
            'tags',
            'entity',
            array(
                'label' => 'mautic.lead.tags',
                'class' => 'MauticLeadBundle:Tag',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.tag', 'ASC');
                },
                'property' => 'tag',
                'multiple' => true,
                'required' => false,
                'attr' => array(
                    'data-placeholder'      => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text'  => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'        => 'true',
                    'onchange'              => 'Mautic.updateLeadTags(this)'
                ),
                'disabled' => (!$options['allow_edit'])
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'allow_edit'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_tags';
    }
}