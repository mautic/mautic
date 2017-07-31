<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export;

use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveDeal;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\AbstractPipedrive;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DealExport extends AbstractPipedrive
{
    public function create(PipedriveDeal $deal, Lead $lead)
    {
        $leadId                = $lead->getId();
        $leadIntegrationEntity = $this->getLeadIntegrationEntity(['internalEntityId' => $leadId]);

        $params = [
            'title'     => $deal->getTitle(),
            'stage_id'  => $deal->getStage()->getStageId(),
            'person_id' => $leadIntegrationEntity->getIntegrationEntityId(),
        ];

        try {
            $response = $this->getIntegration()->getApiHelper()->createDeal($params);
            $dealProducts = $deal->getDealProducts();

            if (count($dealProducts) > 0) {
                $dealProduct = $dealProducts[0]; // only *one* product is allowed in integration form
                $productParams = [
                    'id'          => $response['id'],
                    'product_id'  => $dealProduct->getProduct()->getProductId(),
                    'item_price'  => $dealProduct->getItemPrice(),
                    'quantity'    => $dealProduct->getQuantity(),
                ];

                $this->getIntegration()->getApiHelper()->createDealProduct($productParams);
            }

            return true;
        }
        catch (\Exception $e) {
            $this->getIntegration()->logIntegrationError($e);
        }

        return false;
    }
}
