<?php

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Mautic\EmailBundle\Entity\Email;
use Mautic\InstallBundle\Install\InstallService;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;

class SerializerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private GrapesJsBuilderModel $grapesJsBuilderModel,
        private InstallService $installer,
        private Config $config,
    ) {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event'  => Events::POST_SERIALIZE,
                'method' => 'addCustomMJML',
            ],
        ];
    }

    public function addCustomMJML(ObjectEvent $event): void
    {
        if (!$this->installer->checkIfInstalled()) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $object = $event->getObject();

        if (!$object instanceof Email) {
            return;
        }

        // Get the grapesJsBuilder data.
        $grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $object]);

        // Add it to the serialized data.
        $visitor = $event->getContext()->getVisitor();
        if ($visitor instanceof JsonSerializationVisitor && !empty($grapesJsBuilder->getCustomMjml())) {
            $visitor->visitProperty(
                new StaticPropertyMetadata(
                    '', 'grapesjsbuilder', ['customMjml' => $grapesJsBuilder->getCustomMjml()]
                ), ['customMjml' => $grapesJsBuilder->getCustomMjml()]
            );
        }
    }
}
