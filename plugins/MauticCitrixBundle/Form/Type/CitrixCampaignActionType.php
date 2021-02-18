<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Form\Type;

use Mautic\EmailBundle\Form\Type\EmailListType;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CitrixCampaignActionType.
 */
class CitrixCampaignActionType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * CitrixCampaignActionType constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(array_key_exists('attr', $options) && array_key_exists('data-product', $options['attr']))
            || !CitrixProducts::isValidValue($options['attr']['data-product'])
            || !CitrixHelper::isAuthorized('Goto'.$options['attr']['data-product'])
        ) {
            return;
        }

        $product = $options['attr']['data-product'];

        $choices = [
            'webinar_register'     => $this->translator->trans('plugin.citrix.action.register.webinar'),
            'meeting_start'        => $this->translator->trans('plugin.citrix.action.start.meeting'),
            'training_register'    => $this->translator->trans('plugin.citrix.action.register.training'),
            'training_start'       => $this->translator->trans('plugin.citrix.action.start.training'),
            'assist_screensharing' => $this->translator->trans('plugin.citrix.action.screensharing.assist'),
        ];

        $newChoices = [];
        foreach ($choices as $k => $c) {
            if (0 === mb_strpos($k, $product)) {
                $newChoices[$k] = $c;
            }
        }

        $builder->add(
            'event-criteria-'.$product,
            ChoiceType::class,
            [
                'label'   => $this->translator->trans('plugin.citrix.action.criteria'),
                'choices' => array_flip($newChoices),
            ]
        );

        if (CitrixProducts::GOTOASSIST !== $product) {
            $builder->add(
                $product.'-list',
                ChoiceType::class,
                [
                    'label'    => $this->translator->trans('plugin.citrix.decision.'.$product.'.list'),
                    'choices'  => array_flip(CitrixHelper::getCitrixChoices($product)),
                    'multiple' => true,
                ]
            );
        }

        if (in_array('meeting_start', $newChoices)
            || in_array('training_start', $newChoices)
            || in_array('assist_screensharing', $newChoices)
        ) {
            $defaultOptions = [
                'label'      => 'plugin.citrix.emailtemplate',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'plugin.citrix.emailtemplate_descr',
                ],
                'required'   => true,
                'multiple'   => false,
            ];

            if (array_key_exists('list_options', $options)) {
                if (isset($options['list_options']['attr'])) {
                    $defaultOptions['attr'] = array_merge($defaultOptions['attr'], $options['list_options']['attr']);
                    unset($options['list_options']['attr']);
                }

                $defaultOptions = array_merge($defaultOptions, $options['list_options']);
            }

            $builder->add('template', EmailListType::class, $defaultOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'citrix_campaign_action';
    }
}
