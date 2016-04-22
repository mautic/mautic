<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormSubmitActionAddUtmTagType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FormSubmitActionAddUtmTagsType extends AbstractType
{
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
                'label'           => 'mautic.lead.tags',
                'class'           => 'MauticLeadBundle:Tag',
                'query_builder'   => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->orderBy('t.tag', 'ASC');
                },
                'property'        => 'tag',
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
        return "lead_submitaction_addutmtags";
    }
}
