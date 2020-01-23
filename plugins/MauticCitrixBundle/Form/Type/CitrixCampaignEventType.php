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

use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CitrixCampaignEventType.
 */
class CitrixCampaignEventType extends AbstractType
{
    /**
     * @var CitrixModel
     */
    protected $model;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * CitrixCampaignEventType constructor.
     */
    public function __construct(CitrixModel $model, TranslatorInterface $translator)
    {
        $this->model      = $model;
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

        $product        = $options['attr']['data-product'];
        $eventNamesDesc = $this->model->getDistinctEventNamesDesc($options['attr']['data-product']);

        $choices = [
            'attendedToAtLeast' => $this->translator->trans('plugin.citrix.criteria.'.$product.'.attended'),
        ];

        if (CitrixProducts::GOTOWEBINAR === $product || CitrixProducts::GOTOTRAINING === $product) {
            $choices['registeredToAtLeast'] =
                $this->translator->trans('plugin.citrix.criteria.'.$product.'.registered');
        }

        $builder->add(
            'event-criteria-'.$product,
            ChoiceType::class,
            [
                'label'             => $this->translator->trans('plugin.citrix.decision.criteria'),
                'choices'           => array_flip($choices),
                ]
        );

        $choices = array_replace(
            ['ANY' => $this->translator->trans('plugin.citrix.event.'.$product.'.any')],
            $eventNamesDesc
        );

        $builder->add(
            $product.'-list',
            ChoiceType::class,
            [
                'label'             => $this->translator->trans('plugin.citrix.decision.'.$product.'.list'),
                'choices'           => array_flip($choices),
                'multiple'          => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'citrix_campaign_event';
    }
}
