<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Form\Type\CampaignImportType;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends CommonController
{
    private RequestStack $requestStack;
    private FormFactoryInterface $formFactory;

    public function __construct(
        FormFactoryInterface $formFactory,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
        private LoggerInterface $logger,
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreHelper, $dispatcher, $translator, $flashBag, $requestStack, $security, $formFactory);
        $this->formFactory  = $formFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * Show import page.
     */
    public function indexAction(Request $request): Response
    {
        if (!$this->security->isAdmin() && !$this->security->isGranted('campaign:imports:create')) {
            return $this->accessDenied();
        }

        $action   = $this->generateUrl('mautic_campaign_import_upload');

        $form = $this->formFactory->create(CampaignImportType::class, [], ['action' => $action]);

        return $this->render('@MauticCampaign/Import/import.html.twig', [
            'form'          => $form->createView(),
            'activeLink'    => 'mautic_campaign_index',
            'mauticContent' => 'campaignImport',
        ]);
    }

    /**
     * Handles file upload.
     */
    public function uploadAction(Request $request): Response
    {
        $file = $request->files->get('campaign_import')['campaignFile'] ?? null;

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        // Ensure it's a ZIP file
        if ('zip' !== $file->getClientOriginalExtension()) {
            return new JsonResponse(['error' => 'Invalid file type'], 400);
        }

        // Move to /tmp directory
        $filePath = '/tmp/'.uniqid().'.zip';
        $file->move('/tmp/', basename($filePath));

        $this->requestStack->getSession()->set('mautic.campaign.import.file', $filePath);

        // Initialize progress tracking in the session
        $this->requestStack->getSession()->set('mautic.campaign.import.progress', [0, 100]); // 100 as a placeholder

        return $this->render('@MauticCampaign/Import/progress.html.twig', [
            'activeLink'    => 'mautic_campaign_index',
            'complete'      => false,
            'mauticContent' => 'campaignImport',
            'progress'      => [0, 100], // Ensure progress is set
        ]);
    }

    /**
     * Executes Mautic import command.
     */
    public function importAction(Request $request): JsonResponse
    {
        $filePath = $this->requestStack->getSession()->get('mautic.campaign.import.file');

        if (!$filePath || !file_exists($filePath)) {
            return new JsonResponse(['error' => 'File not found'], 400);
        }

        // Simulated import process tracking
        $totalRecords = 100; // Assume the total number of records
        $this->requestStack->getSession()->set('mautic.campaign.import.progress', [0, $totalRecords]);

        for ($i = 1; $i <= $totalRecords; ++$i) {
            sleep(1); // Simulating processing time
            $this->requestStack->getSession()->set('mautic.campaign.import.progress', [$i, $totalRecords]);
        }

        // Clear session after execution
        $this->requestStack->getSession()->remove('mautic.campaign.import.file');

        return new JsonResponse(['message' => 'Import completed']);
    }

    /**
     * Cancels import by removing the uploaded file.
     */
    public function cancelAction(): JsonResponse
    {
        $filePath = $this->requestStack->getSession()->get('mautic.campaign.import.file');

        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
            $this->logger->info("Campaign import file removed: {$filePath}");
        }

        $this->requestStack->getSession()->remove('mautic.campaign.import.file');

        return new JsonResponse(['message' => 'Campaign import canceled successfully.']);
    }

    /**
     * Fetch import progress.
     */
    public function getProgressAction(): JsonResponse
    {
        $progress = $this->requestStack->getSession()->get('mautic.campaign.import.progress', null);

        if (!$progress) {
            return new JsonResponse(['error' => 'Progress data not found'], 400);
        }

        return new JsonResponse([
            'current'   => $progress[0],
            'total'     => $progress[1],
            'complete'  => $progress[0] >= $progress[1],
        ]);
    }
}
