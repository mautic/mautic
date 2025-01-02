<?php

namespace Mautic\EmailBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Form\Type\GenerateABTestType;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ABTestController extends AbstractFormController
{
    public const DEFAULT_DELAY            = 24;

    public const TOTAL_WEIGHT = 10;

    public function __construct(
        private EmailModel $emailModel,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function generateABTestAction(Request $request, int $objectId)
    {
        if (!$parent = $this->emailModel->getEntity($objectId)) {
            return $this->notFound();
        }

        $action = $this->generateUrl('mautic_abtest_generate', ['objectId' => $objectId]);
        $data1  = $parent->getVariantSettings();
        $form   =  $this->createForm(GenerateABTestType::class, $data1, ['action' => $action]);

        if ('POST' == $request->getMethod()) {
            $isCancelled    = $this->isFormCancelled($form);
            $isValid        = $this->isFormValid($form);
            $data           = $form->getData();

            if (!$isCancelled && $isValid) {
                $this->updateExistingParentVariant($parent, $data);
            }

            if ($isCancelled || $isValid) {
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $objectId,
                ];

                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $this->generateUrl('mautic_email_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'Mautic\EmailBundle\Controller\EmailController::viewAction',
                        'passthroughVars' => [
                            'mauticContent' => 'email',
                            'closeModal'    => 1,
                        ],
                    ]
                );
            }
        }

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'form'                  => $form->createView(),
                ],
                'contentTemplate' => '@MauticEmail/Email/abtest.html.twig',
            ]
        );
    }

    protected function updateExistingParentVariant(\Mautic\EmailBundle\Entity\Email $parent, array $data): void
    {
        $variantSettings                    = $parent->getVariantSettings();
        $variantSettings['winnerCriteria']  = $data['winnerCriteria'] ?? 'email.openrate';
        $variantSettings['sendWinnerDelay'] = $data['sendWinnerDelay'] ?? self::DEFAULT_DELAY;
        $variantSettings['totalWeight']     = $data['totalWeight'] ?? AbTestSettingsService::DEFAULT_AB_WEIGHT;
        $variantSettings['enableAbTest']    = 1;

        $parent->setVariantSettings($variantSettings);

        $this->emailModel->saveEntity($parent);
    }
}
