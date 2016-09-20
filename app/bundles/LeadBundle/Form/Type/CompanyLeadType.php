<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Form\DataTransformer\CompanyEntityTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CompanyLeadType extends AbstractType
{
    /**
     * @var
     */
    private $em;


    /**
     * @param EntityManager $entityManager
     *
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

            $transformer = new CompanyEntityTransformer(
                $this->em,
                'MauticLeadBundle:Company',
                'id',
                ($options['multiple']),
                true
            );

            $builder->addModelTransformer($transformer);

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'label'           => 'mautic.lead.companies',
                'class'           => 'MauticLeadBundle:Company',
                'query_builder'   => function (EntityRepository $er) {
                    return $er->createQueryBuilder('comp');
                },
                'property'        => 'companies',
                'multiple'        => true,
                'required'        => false,
                'disabled'        => false
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'company_lead';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'entity';
    }
}