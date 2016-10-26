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

use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CitrixCampaignActionType
 */
class CitrixCampaignActionType extends AbstractType
{

    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(array_key_exists('attr', $options) && array_key_exists('data-product', $options['attr'])) ||
            !CitrixProducts::isValidValue($options['attr']['data-product']) ||
            !CitrixHelper::isAuthorized('Goto'.$options['attr']['data-product'])
        ) {
            return;
        }

        $product = $options['attr']['data-product'];

        /** @var Translator $translator */
        $translator = CitrixHelper::getContainer()->get('translator');

        $choices = [
            'webinar_register' => $translator->trans('plugin.citrix.action.register.webinar'),
            'meeting_start' => $translator->trans('plugin.citrix.action.start.meeting'),
            'training_register' => $translator->trans('plugin.citrix.action.register.training'),
            'training_start' => $translator->trans('plugin.citrix.action.start.training'),
            'assist_screensharing' => $translator->trans('plugin.citrix.action.screensharing.assist'),
        ];

        $newChoices = [];
        foreach ($choices as $k => $c) {
            if (strpos($k, $product) === 0) {
                $newChoices[$k] = $c;
            }
        }

        $builder->add(
            'event-criteria-'.$product,
            'choice',
            [
                'label' => $translator->trans('plugin.citrix.action.criteria'),
                'choices' => $newChoices,
            ]
        );

        if (CitrixProducts::GOTOASSIST !== $product) {
            $builder->add(
                $product.'-list',
                'choice',
                [
                    'label' => $translator->trans('plugin.citrix.decision.'.$product.'.list'),
                    'choices' => CitrixHelper::getCitrixChoices($product),
                    'multiple' => true
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'citrix_campaign_action';
    }
}
