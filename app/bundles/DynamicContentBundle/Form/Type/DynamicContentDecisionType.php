<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class DynamicContentDecisionType.
 */
class DynamicContentDecisionType extends DynamicContentSendType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'dwc_slot_name',
            'text',
            [
                'label' => 'mautic.dynamicContent.send.slot_name',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'mautic.dynamicContent.send.slot_name.tooltip',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'mautic.dynamicContent.slot_name.notblank']),
                ],
            ]
        );

        parent::buildForm($builder, $options);

        $builder->add(
            'dynamicContent',
            'dwc_list',
            [
                'label' => 'mautic.dynamicContent.send.selectDynamicContents.default',
                'label_attr' => ['class' => 'control-label'],
                'attr' => [
                    'class' => 'form-control',
                    'tooltip' => 'mautic.dynamicContent.choose.dynamicContents',
                    'onchange' => 'Mautic.disabledDynamicContentAction()',
                ],
                'multiple' => false,
                'constraints' => [
                    new NotBlank(['message' => 'mautic.dynamicContent.choosedynamicContent.notblank']),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dwcdecision_list';
    }
}
