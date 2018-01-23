<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadFieldsType.
 */
class LeadFieldsType extends AbstractType
{
    private $model;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->model = $factory->getModel('lead.field');
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        /** @var \Mautic\LeadBundle\Model\FieldModel $model */
        $model = $this->model;
        $resolver->setDefaults([
            'choices' => function (Options $options) use ($model) {
                $fieldList = array_merge($model->getFieldList(), $model->getFieldList(true, true, ['isPublished' => true, 'object' => 'company']));
                if ($options['with_tags']) {
                    $fieldList['Core']['tags'] = 'mautic.lead.field.tags';
                }

                return $fieldList;
            },
            'global_only' => false,
            'required'    => false,
            'with_tags'   => false,
        ]);
    }

    /**
     * @return null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadfields_choices';
    }
}
