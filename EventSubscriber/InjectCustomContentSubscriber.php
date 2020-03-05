<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\HttpFoundation\RequestStack;

class InjectCustomContentSubscriber extends CommonSubscriber
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
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * InjectCustomContentSubscriber constructor.
     *
     * @param Config               $config
     * @param GrapesJsBuilderModel $grapesJsBuilderModel
     * @param TemplatingHelper     $templatingHelper
     * @param RequestStack         $requestStack
     */
    public function __construct(Config $config, GrapesJsBuilderModel $grapesJsBuilderModel, TemplatingHelper $templatingHelper, RequestStack $requestStack)
    {
        $this->config               = $config;
        $this->grapesJsBuilderModel = $grapesJsBuilderModel;
        $this->templatingHelper     = $templatingHelper;
        $this->requestStack         = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectViewCustomContent', 0],
        ];
    }

    /**
     * @param CustomContentEvent $customContentEvent
     */
    public function injectViewCustomContent(CustomContentEvent $customContentEvent)
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $parameters = $customContentEvent->getVars();

        if ($customContentEvent->getContext() != 'email.settings.advanced') {
            return;
        } elseif (empty($parameters['email']) || !$parameters['email'] instanceof Email) {
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
        if ($grapesJsBuilder instanceof GrapesJsBuilder && $this->requestStack->getCurrentRequest()->getMethod() !== 'POST') {
            $passParams['customMjml'] = $grapesJsBuilder->getCustomMjml();
        }

        $content = $this->templatingHelper->getTemplating()->render(
            'GrapesJsBuilderBundle:Email:settings.html.php',
            $passParams
        );

        $customContentEvent->addContent($content);
    }
}
