<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class InjectCustomContentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
        private GrapesJsBuilderModel $grapesJsBuilderModel,
        private Environment $twig,
        private RequestStack $requestStack,
        private RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectViewCustomContent', 0],
        ];
    }

    public function injectViewCustomContent(CustomContentEvent $customContentEvent): void
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
                if (!$grapesJsBuilder instanceof GrapesJsBuilder && $parameters['email']->getIsClone()) {
                    $grapesJsBuilder = $this->grapesJsBuilderModel->getGrapesJsFromEmailId(
                        $parameters['email']->getClonedId()
                    );
                }

                if ($grapesJsBuilder instanceof GrapesJsBuilder) {
                    $passParams['customMjml'] = $grapesJsBuilder->getCustomMjml();
                }
            }
            $content = $this->twig->render(
                '@GrapesJsBuilder/Setting/fields.html.twig',
                $passParams
            );

            $customContentEvent->addContent($content);
        } elseif ('page.header.left' === $customContentEvent->getContext()) {
            // Inject fileManager URL
            $passParams['dataAssets'] = $this->router->generate('grapesjsbuilder_assets', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            $passParams['dataUpload'] = $this->router->generate('grapesjsbuilder_upload', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            $passParams['dataDelete'] = $this->router->generate('grapesjsbuilder_delete', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $content = $this->twig->render(
                '@GrapesJsBuilder/Setting/vars.html.twig',
                $passParams
            );

            $customContentEvent->addContent($content);
        }
    }
}
