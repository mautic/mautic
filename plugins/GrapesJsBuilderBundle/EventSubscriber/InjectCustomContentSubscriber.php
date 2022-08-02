<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class InjectCustomContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var GrapesJsBuilderModel
     */
    private $grapesJsBuilderModel;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * InjectCustomContentSubscriber constructor.
     */
    public function __construct(Config $config, GrapesJsBuilderModel $grapesJsBuilderModel, FileManager $fileManager, TemplatingHelper $templatingHelper, RequestStack $requestStack, RouterInterface $router)
    {
        $this->config               = $config;
        $this->grapesJsBuilderModel = $grapesJsBuilderModel;
        $this->fileManager          = $fileManager;
        $this->templatingHelper     = $templatingHelper;
        $this->requestStack         = $requestStack;
        $this->router               = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectViewCustomContent', 0],
        ];
    }

    public function injectViewCustomContent(CustomContentEvent $customContentEvent)
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $passParams = [];
        $parameters = $customContentEvent->getVars();

        if ('email.settings.advanced' === $customContentEvent->getContext()) {
            // Inject MJML form within mail page
            if (empty($parameters['email']) || !$parameters['email'] instanceof Email) {
                return;
            }

            $passParams = ['customMjml' => ''];
            if ($this->requestStack->getCurrentRequest()->request->has('grapesjsbuilder')) {
                $data = $this->requestStack->getCurrentRequest()->get('grapesjsbuilder', '');

                if (isset($data['customMjml'])) {
                    $passParams['customMjml'] = $data['customMjml'];
                }
            }

            $grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $parameters['email']]);
            if ('POST' !== $this->requestStack->getCurrentRequest()->getMethod()) {
                if (!$grapesJsBuilder instanceof GrapesJsBuilder && $parameters['email']->getClonedId()) {
                    $grapesJsBuilder = $this->grapesJsBuilderModel->getGrapesJsFromEmailId(
                        $parameters['email']->getClonedId()
                    );
                }

                if ($grapesJsBuilder instanceof GrapesJsBuilder) {
                    $passParams['customMjml'] = $grapesJsBuilder->getCustomMjml();
                }
            }
            $content = $this->templatingHelper->getTemplating()->render(
                'GrapesJsBuilderBundle:Setting:fields.html.php',
                $passParams
            );

            $customContentEvent->addContent($content);
        } elseif ('page.header.left' === $customContentEvent->getContext()) {
            // Inject fileManager URL and list of images within all pages
            $passParams['assets']     = json_encode($this->fileManager->getImages());
            $passParams['dataUpload'] = $this->router->generate('grapesjsbuilder_upload', [], true);
            $passParams['dataDelete'] = $this->router->generate('grapesjsbuilder_delete', [], true);

            $content = $this->templatingHelper->getTemplating()->render(
                'GrapesJsBuilderBundle:Setting:vars.html.php',
                $passParams
            );

            $customContentEvent->addContent($content);
        }
    }
}
