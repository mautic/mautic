<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\DynamicContentBundle\Controller\Api;

use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event\DynamicContentEvent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DynamicContentApiController.
 */
class DynamicContentApiController extends CommonController
{
    /**
     * @param $objectAlias
     *
     * @return mixed
     */
    public function processAction($objectAlias)
    {
        $method = $this->request->getMethod();

        if (method_exists($this, $method.'Action')) {
            return $this->{$method.'Action'}($objectAlias);
        } else {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'This endpoint is not able to process '.strtoupper($method).' requests.');
        }
    }

    public function getAction($objectAlias)
    {
        /** @var EventModel $campaignEventModel */
        $campaignEventModel = $this->getModel('campaign.event');

        $response = $campaignEventModel->triggerEvent('dwc.decision', $objectAlias, 'dwc.decision.' . $objectAlias);
        $content  = null;
        $lead     = $this->getModel('lead')->getCurrentLead();

        if (is_array($response) && !empty($response['action']['dwc.push_content'])) {
            $content = array_shift($response['action']['dwc.push_content']);
        } else {
            /** @var DynamicContentModel $dwcModel */
            $dwcModel = $this->getModel('dynamicContent');

            $data = $dwcModel->getSlotContentForLead($objectAlias, $lead);

            if (!empty($data)) {
                $content = $data['content'];
                $dwc = $dwcModel->getEntity($data['id']);

                if ($dwc instanceof DynamicContent) {
                    $dwcModel->createStatEntry($dwc, $lead, $objectAlias);

                    $tokenEvent = new TokenReplacementEvent($content, $lead, ['slot' => $objectAlias, 'dynamic_content_id' => $dwc->getId()]);
                    $this->factory->getDispatcher()->dispatch(DynamicContentEvents::TOKEN_REPLACEMENT, $tokenEvent);
                    
                    $content = $tokenEvent->getContent();
                }
            }
        }
        
        return empty($content) ? new Response('', Response::HTTP_NOT_FOUND) : new Response($content);
    }
}
