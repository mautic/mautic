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
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Form\DataTransformer\CompanyEntityTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CompanyLeadType extends AbstractType
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
            $transformer = new CompanyEntityTransformer(
                $this->factory->getEntityManager(),
                'MauticLeadBundle:Company',
                'id',
                ($options['multiple']),
                true
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
            array(
                'label'           => 'mautic.lead.companies',
                'class'           => 'MauticLeadBundle:Company',
                'query_builder'   => function (EntityRepository $er) {
                    return $er->createQueryBuilder('comp');
                },
                'property'        => 'companies',
                'multiple'        => true,
                'required'        => false,
                'disabled'        => false,
                'add_transformer' => true
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