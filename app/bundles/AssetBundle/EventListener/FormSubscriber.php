<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Form\Type\FormSubmitActionDownloadFileType;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Templating\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class FormSubscriber implements EventSubscriberInterface
{
    /**
     * @var AssetModel
     */
    private $assetModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var AnalyticsHelper
     */
    private $analyticsHelper;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var ThemeHelperInterface
     */
    private $themeHelper;

    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(
        AssetModel $assetModel,
        TranslatorInterface $translator,
        AnalyticsHelper $analyticsHelper,
        AssetsHelper $assetsHelper,
        ThemeHelperInterface $themeHelper,
        TemplatingHelper $templatingHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->assetModel           = $assetModel;
        $this->translator           = $translator;
        $this->analyticsHelper      = $analyticsHelper;
        $this->assetsHelper         = $assetsHelper;
        $this->themeHelper          = $themeHelper;
        $this->templatingHelper     = $templatingHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
    public function onFormBuilder(FormBuilderEvent $event)
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
            } catch (NoResultException | NonUniqueResultException $e) {
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
         * @var Form
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
        $url = $this->assetModel->generateUrl($asset, true, ['form', $form->getId()]);

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
            $this->templatingHelper->getTemplating()->renderResponse(
                $this->themeHelper->checkForTwigTemplate(':'.$this->coreParametersHelper->get('theme').':message.html.php'),
                [
                    'message'  => $msg,
                    'type'     => 'notice',
                    'template' => $this->coreParametersHelper->get('theme'),
                ]
            )->getContent()
        ));
    }
}
