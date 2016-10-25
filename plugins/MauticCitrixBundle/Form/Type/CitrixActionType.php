<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Form\Type;

use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class FormFieldSelectType.
 */
class CitrixActionType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $modelFactory = CitrixHelper::getContainer()->get('mautic.model.factory');
        $model = $modelFactory->getModel('form.field');
        $fields = $model->getSessionFields($options['attr']['data-formid']);

        $options = [
            ''=>'',
        ];
        foreach ($fields as $f) {
            if (in_array($f['type'], array('button', 'freetext', 'captcha'))) {
                continue;
            }
            $options[$f['id']] = $f['label'];
        }

        $builder->add(
            'formfields',
            'choice',
            array(
                'choices' => $options,
                'expanded' => false,
                'label_attr' => array('class' => 'control-label'),
                'multiple' => false,
                'label' => 'mautic.integration.desk.selectidentifier',
                'attr' => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.integration.desk.selectidentifier.tooltip',
                ),
                'required' => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            )
        );

        $builder->add(
            'first_name',
            'choice',
            array(
                'choices' => $options,
                'expanded' => false,
                'label_attr' => array('class' => 'control-label'),
                'multiple' => false,
                'label' => 'mautic.integration.desk.first_name',
                'attr' => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.integration.desk.first_name.tooltip',
                ),
                'required' => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            )
        );

        $builder->add(
            'last_name',
            'choice',
            array(
                'choices' => $options,
                'expanded' => false,
                'label_attr' => array('class' => 'control-label'),
                'multiple' => false,
                'label' => 'mautic.integration.desk.last_name',
                'attr' => array(
                    'class' => 'form-control',
                    'tooltip' => 'mautic.integration.desk.last_name.tooltip',
                ),
                'required' => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'citrix_submit_action';
    }
}
