<?php

namespace MauticPlugin\MauticCrmBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDealProduct;

class EventHelper
{

    /**
     * @param               $lead
     * @param MauticFactory $factory
     */
    public static function pushOffer($config, $lead, MauticFactory $factory)
    {
        $integrationHelper = $factory->get('mautic.helper.integration');
        $myIntegration     = $integrationHelper->getIntegrationObject('Pipedrive');

        $em = $factory->getEntityManager();

        $leadExport = $factory->get('mautic_integration.pipedrive.export.lead');
        $leadExport->setIntegration($myIntegration);
        //$leadCreated = $leadExport->create($lead);

        $dealExport = $factory->get('mautic_integration.pipedrive.export.deal');
        $dealExport->setIntegration($myIntegration);

        $deal = new PipedriveDeal();
        $deal->setTitle($config['title']);
        $deal->setStage(
            $em->getReference('MauticPlugin\MauticCrmBundle\Entity\PipedriveStage', $config['stage'])
        );
        $deal->setLead($lead);

        $dealProduct = new PipedriveDealProduct();
        $dealProduct->setDeal($deal);
        $dealProduct->setProduct(
            $em->getReference('MauticPlugin\MauticCrmBundle\Entity\PipedriveProduct', $config['product'])
        );
        $dealProduct->setQuantity(1);
        $dealProduct->setItemPrice($config['product_price']);

        $deal->addDealProduct($dealProduct);

        return $dealExport->create($deal, $lead);
    }

}
