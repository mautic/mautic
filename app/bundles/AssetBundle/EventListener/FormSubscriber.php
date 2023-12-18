<?php

namespace Mautic\AssetBundle\EventListener;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Form\Type\FormSubmitActionDownloadFileType;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class FormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetModel $assetModel,
        protected TranslatorInterface $translator,
        private AnalyticsHelper $analyticsHelper,
        private AssetsHelper $assetsHelper,
        private ThemeHelperInterface $themeHelper,
        private Environment $twig,
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_ON_BUILD                 => ['onFormBuilder', 0],
            FormEvents::ON_EXECUTE_SUBMIT_ACTION      => [
                ['onFormSubmitActionAssetDownload', 0],
                ['onFormSubmitActionDownloadFile', 0],
            ],
        ];
    }

    /**
     * Add a lead generation action to available form submit actions.
     */
    public function onFormBuilder(FormBuilderEvent $event): void
    {
        $event->addSubmitAction('asset.download', [
            'group'              => 'mautic.asset.actions',
            'label'              => 'mautic.asset.asset.submitaction.downloadfile',
            'description'        => 'mautic.asset.asset.submitaction.downloadfile_descr',
            'formType'           => FormSubmitActionDownloadFileType::class,
            'formTypeCleanMasks' => ['message' => 'html'],
            'eventName'          => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm'  => true,
        ]);
    }

    public function onFormSubmitActionAssetDownload(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('asset.download')) {
            return;
        }

        $properties = $event->getAction()->getProperties();
        $assetId    = $properties['asset'];
        $categoryId = $properties['category'] ?? null;
        $asset      = null;

        if (null !== $assetId) {
            $asset = $this->assetModel->getEntity($assetId);
        } elseif (null !== $categoryId) {
            try {
                $asset = $this->assetModel->getRepository()->getLatestAssetForCategory($categoryId);
            } catch (NoResultException|NonUniqueResultException) {
                $asset = null;
            }
        }

        if ($asset instanceof Asset && $asset->isPublished()) {
            $event->setPostSubmitCallback('asset.download_file', [
                'eventName' => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
                'form'      => $event->getAction()->getForm(),
                'asset'     => $asset,
                'message'   => $properties['message'] ?? '',
            ]);
        }
    }

    public function onFormSubmitActionDownloadFile(SubmissionEvent $event): void
    {
        if (false === $event->checkContext('asset.download_file')) {
            return;
        }

        /*
         * No further actions can run after this, as we need to send the
         * download response to the client.
         */
        $event->stopPropagation();

        /**
         * @var Form   $form
         * @var Asset  $asset
         * @var string $message
         * @var bool   $messengerMode
         */
        [
            'form'          => $form,
            'asset'         => $asset,
            'message'       => $message,
            'messengerMode' => $messengerMode,
        ]    = $event->getPostSubmitCallback('asset.download_file');

        $url = $this->assetModel->generateUrl($asset, true, [
            'lead'    => $event->getLead() ? $event->getLead()->getId() : null,
            'channel' => ['form' => $form->getId()],
            ]).'&stream=0';

        if ($messengerMode) {
            $event->setPostSubmitResponse(['download' => $url]);

            return;
        }

        $msg = $message.$this->translator->trans('mautic.asset.asset.submitaction.downloadfile.msg', [
            '%url%' => $url,
        ]);

        $analytics = $this->analyticsHelper->getCode();

        if (!empty($analytics)) {
            $this->assetsHelper->addCustomDeclaration($analytics);
        }

        $event->setPostSubmitResponse(new Response(
            $this->twig->render(
                $this->themeHelper->checkForTwigTemplate('@themes/'.$this->coreParametersHelper->get('theme').'/html/message.html.twig'),
                [
                    'message'  => $msg,
                    'type'     => 'notice',
                    'template' => $this->coreParametersHelper->get('theme'),
                ]
            )
        ));
    }
}
