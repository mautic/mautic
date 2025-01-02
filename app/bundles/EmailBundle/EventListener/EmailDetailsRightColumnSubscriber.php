<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\EmailBundle\Model\AbTest\EmailStatus;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailDetailsRightColumnSubscriber implements EventSubscriberInterface
{
    public function __construct(private EmailModel $emailModel)
    {
    }


    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectContent', 0],
        ];
    }

    public function injectContent(CustomContentEvent $event): void
    {
        if ($event->checkContext('@MauticEmail/Email/details.html.twig', 'right.section.start')) {
            $vars = $event->getVars();
            $email = $vars['email'];

            $data = [
                'email' => $email,
                'emailStatus'    => new EmailStatus($email, 0, $this->emailModel->getPublishStatus($email)),

            ];
            $event->addTemplate('@MauticEmail/Email/abdetails.html.twig', $data);

        }

    }

    /**
     * Apart from fetching the custom object list this method also caches them to the memory and
     * use the list from memory if called multiple times.
     *
     * @return CustomObject[]
     */
    private function getCustomObjects(): array
    {
        if (!$this->customObjects) {
            $this->customObjects = $this->customObjectModel->fetchAllPublishedEntities();
        }

        return $this->customObjects;
    }
}
