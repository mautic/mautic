<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeadFieldsType extends AbstractType
{
    /**
     * @var FieldModel
     */
    protected $fieldModel;

    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $fieldList = ArrayHelper::flipArray($this->fieldModel->getFieldList());
                if ($options['with_tags']) {
                    $fieldList['Core']['mautic.lead.field.tags'] = 'tags';
                }
                if ($options['with_company_fields']) {
                    $fieldList['Company'] = array_flip($this->fieldModel->getFieldList(false, true, ['isPublished' => true, 'object' => 'company']));
                }
                if ($options['with_utm']) {
                    $fieldList['UTM']['mautic.lead.field.utmcampaign'] = 'utm_campaign';
                    $fieldList['UTM']['mautic.lead.field.utmcontent']  = 'utm_content';
                    $fieldList['UTM']['mautic.lead.field.utmmedium']   = 'utm_medium';
                    $fieldList['UTM']['mautic.lead.field.umtsource']   = 'utm_source';
                    $fieldList['UTM']['mautic.lead.field.utmterm']     = 'utm_term';
                }

                return $fieldList;
            },
            'global_only'         => false,
            'required'            => false,
            'with_company_fields' => false,
            'with_tags'           => false,
            'with_utm'            => false,
        ]);
    }

    /**
     * @return string|\Symfony\Component\Form\FormTypeInterface|null
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadfields_choices';
    }
}
