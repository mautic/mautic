<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\FormBundle\Event\FormBuilderEvent;
use MauticPlugin\MauticSocialBundle\Form\Type\SocialLoginType;
use MauticPlugin\MauticSocialBundle\Integration\Config;

final readonly class FormSubscriber
{
    public function __construct(
        private Config $config,
    ) {
    }

    #[AsEventListener(event: FormBuilderEvent::class)]
    public function onFormBuild(FormBuilderEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }
        $action = [
            'label'          => 'mautic.plugin.actions.socialLogin',
            'formType'       => SocialLoginType::class,
            'template'       => '@MauticSocial/Integration/login.html.twig',
            'builderOptions' => [
                'addLeadFieldList' => false,
                'addIsRequired'    => false,
                'addDefaultValue'  => false,
                'addSaveResult'    => false,
            ],
        ];

        $event->addFormField('plugin.loginSocial', $action);
    }
}
