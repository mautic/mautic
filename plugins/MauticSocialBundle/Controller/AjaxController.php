<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;
    private FormFactoryInterface $formFactory;

    public function getNetworkFormAction(Request $request, MonitoringModel $monitoringModel): JsonResponse
    {
        // get the form type
        $type = InputHelper::clean($request->request->get('networkType'));

        // default to empty
        $dataArray = [
            'html'    => '',
            'success' => 0,
        ];

        if (!empty($type)) {
            // get the HTML for the form

            $formType = $monitoringModel->getFormByType($type);

            // get the network type form
            $form = $this->formFactory->create($formType, [], ['label' => false, 'csrf_protection' => false]);

            $html = $this->renderView(
                '@MauticSocial/FormTheme/'.$type.'_widget.html.twig',
                ['form' => $form->createView()]
            );

            $html = str_replace(
                [
                    $type.'[', // this is going to generate twitter_hashtag[ or twitter_mention[
                    $type.'_', // this is going to generate twitter_hashtag_ or twitter_mention_
                    $type,
                ],
                [
                    'monitoring[properties][',
                    'monitoring_properties_',
                    'monitoring',
                ],
                $html
            );

            $dataArray['html']    = $html;
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireSocialAjaxController(
        FormFactoryInterface $formFactory,
    ): void {
        $this->formFactory = $formFactory;
    }
}
