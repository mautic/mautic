<?php

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    protected function slotNameListAction(Request $request): JsonResponse
    {
        $filter    = InputHelper::clean($request->query->get('filter'));

        /** @var DynamicContentModel $model */
        $model     = $this->getModel('dynamicContent');
        $results   = $model->getLookupResults('slot_name', $filter, 10);

        return $this->sendJsonResponse($results);
    }

    public function getDwcTokensBySlotNameAction(Request $request): JsonResponse
    {
        $slotName = InputHelper::clean($request->query->get('slotName'));

        /** @var DynamicContentRepository $dynamicContentRepository */
        $dynamicContentRepository = $this->getModel('dynamicContent')->getRepository();
        $dwcTokens                = $dynamicContentRepository->getDynamicContentBySlotName($slotName);

        $displayOrderArray = [];
        foreach ($dwcTokens as $dwcToken) {
            $displayOrderArray["({$dwcToken['display_order']}) {$dwcToken['name']}"] = (int) $dwcToken['display_order'];
        }

        return $this->sendJsonResponse(['display_orders' => $displayOrderArray]);
    }

    /**
     * Called by parent::getBuilderTokensAction().
     *
     * @param mixed $query
     *
     * @return mixed[]
     */
    protected function getBuilderTokens($query): array
    {
        $pageToken  = $this->getTokens('page');
        $emailToken = $this->getTokens('email');

        return ['tokens' => array_merge($pageToken['tokens'] ?? [], $emailToken['tokens'] ?? [])];
    }

    /**
     * @return mixed[]
     */
    private function getTokens(string $modelName): array
    {
        /** @var PageModel|EmailModel $model */
        $model = $this->getModel($modelName);

        return $model->getBuilderComponents(null, ['tokens']);
    }
}
