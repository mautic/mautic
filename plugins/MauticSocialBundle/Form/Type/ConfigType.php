<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    protected $fieldModel;

    /**
     * ConfigType constructor.
     */
    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $leadFields = $this->fieldModel->getFieldList(false, false);

        $builder->add(
            'twitter_handle_field',
            ChoiceType::class,
            [
                'choices'           => array_flip($leadFields),
                'label'             => 'mautic.social.config.twitter.field.label',
                'required'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'social_config';
    }
}
