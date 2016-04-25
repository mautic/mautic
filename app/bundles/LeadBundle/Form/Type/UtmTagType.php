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
use Mautic\LeadBundle\Form\DataTransformer\UtmTagEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UtmTagType extends AbstractType
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
            $transformer = new UtmTagEntityModelTransformer(
                $this->factory->getEntityManager(),
                'MauticLeadBundle:UtmTag',
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
                'label'           => 'mautic.lead.utmtags',
                'class'           => 'MauticLeadBundle:UtmTag',
                'query_builder'   => function (EntityRepository $er) {
                    return $er->createQueryBuilder('ut');
                },
                'property'        => 'utmTag',
                'multiple'        => true,
                'required'        => false,
                'disabled'        => false,
                'add_transformer' => false
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_utmtag';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'entity';
    }
}
