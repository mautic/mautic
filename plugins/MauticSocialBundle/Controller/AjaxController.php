<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticSocialBundle\Model\MonitoringModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        protected \Doctrine\Persistence\ManagerRegistry $doctrine,
        protected ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        protected \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        protected \Mautic\CoreBundle\Translation\Translator $translator,
        private \Mautic\CoreBundle\Service\FlashBag $flashBag,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly FormFactoryInterface $formFactory,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

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
}
