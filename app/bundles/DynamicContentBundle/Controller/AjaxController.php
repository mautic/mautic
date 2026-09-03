<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;
    private DynamicContentRepository $dynamicContentRepository;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireAjaxController(
        DynamicContentRepository $dynamicContentRepository
    ): void
    {
        $this->dynamicContentRepository = $dynamicContentRepository;
    }

    public function slotNameListAction(Request $request): JsonResponse
    {
        $filter    = InputHelper::clean($request->query->get('filter'));

        /** @var DynamicContentModel $model */
        $model     = $this->getModel('dynamicContent');
        $results   = $model->getLookupResults('slot_name', $filter, 10);

        return $this->sendJsonResponse($results);
    }

    /**
     * @throws \Exception
     */
    public function getDwcTokensBySlotNameAction(Request $request): JsonResponse
    {
        $displayOrderArray    = [];
        $dwcId                = InputHelper::clean($request->query->get('id'));
        $slotName             = InputHelper::clean($request->query->get('slotName'));
        $includeDefaultOption = InputHelper::clean($request->query->get('includeDefaultOption'));

        if ($includeDefaultOption) {
            $displayOrderArray[$this->translator->trans('mautic.dynamicContent.choose.placeholder')] = [
                'value'    => '',
                'selected' => true,
            ];
            $displayOrderArray[$this->translator->trans('mautic.dynamicContent.choose.default.order')] = [
                'value'    => 0,
                'selected' => false,
            ];
        }

        if (!empty($slotName)) {
            $dynamicContentRepository = $this->dynamicContentRepository;
            $dynamicContent           = $dynamicContentRepository->getEntity($dwcId);
            $dwcTokens                = $dynamicContentRepository->getDynamicContentBySlotName($slotName);

            if (empty($dynamicContent)) {
                foreach ($dwcTokens as $dwcToken) {
                    $displayOrderArray["({$dwcToken['display_order']}) {$dwcToken['name']}"] = [
                        'value'    => (int) $dwcToken['display_order'],
                        'selected' => false,
                    ];
                }

                return $this->sendJsonResponse(['display_orders' => $displayOrderArray]);
            }

            $dwcDisplayOrder = $dynamicContent->getDisplayOrder();
            $dwcSlotName     = $dynamicContent->getSlotName();

            $displayOrderArray[$this->translator->trans('mautic.dynamicContent.choose.default.order')] = [
                'value'    => 0,
                'selected' => 0 == $dwcDisplayOrder - 1,
            ];

            foreach ($dwcTokens as $dwcToken) {
                if ($dwcDisplayOrder != $dwcToken['display_order'] || $dwcSlotName != $slotName) {
                    $displayOrderArray["({$dwcToken['display_order']}) {$dwcToken['name']}"] = [
                        'value'    => (int) $dwcToken['display_order'],
                        'selected' => $dwcToken['display_order'] == $dwcDisplayOrder - 1,
                    ];
                }
            }
        }

        return $this->sendJsonResponse(['display_orders' => $displayOrderArray]);
    }
}
