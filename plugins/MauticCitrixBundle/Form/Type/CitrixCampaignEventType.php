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
 * Class CitrixCampaignEventType
 */
class CitrixCampaignEventType extends AbstractType
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
        /** @var CitrixModel $citrixModel */
        $citrixModel = CitrixHelper::getContainer()->get('mautic.model.factory')->getModel('citrix');
        $eventNames = $citrixModel->getDistinctEventNames($options['attr']['data-product']);

        $choices = [
            'attendedToAtLeast' => $translator->trans('plugin.citrix.criteria.'.$product.'.attended'),
        ];

        if (CitrixProducts::GOTOWEBINAR === $product || CitrixProducts::GOTOTRAINING === $product) {
            $choices['registeredToAtLeast'] =
                 $translator->trans('plugin.citrix.criteria.'.$product.'.registered');
        }

        $builder->add(
            'event-criteria-'.$product,
            'choice',
            [
                'label' => $translator->trans('plugin.citrix.decision.criteria'),
                'choices' => $choices,
            ]
        );

        $choices = array_replace(
            ['ANY' => $translator->trans('plugin.citrix.event.'.$product.'.any')],
            array_combine($eventNames, $eventNames)
        );

        $builder->add(
            $product.'-list',
            'choice',
            [
                'label' => $translator->trans('plugin.citrix.decision.'.$product.'.list'),
                'choices' => $choices,
                'multiple' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'citrix_campaign_event';
    }
}
