<?php

namespace Mautic\ApiBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PreAuthorizationEventListener implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private CorePermissions $mauticSecurity,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OAuthEvent::PRE_AUTHORIZATION_PROCESS  => 'onPreAuthorizationProcess',
            OAuthEvent::POST_AUTHORIZATION_PROCESS => 'onPostAuthorizationProcess',
        ];
    }

    /**
     * @throws AccessDeniedException
     */
    public function onPreAuthorizationProcess(OAuthEvent $event): void
    {
        if ($user = $this->getUser($event)) {
            // check to see if user has api access
            if (!$this->mauticSecurity->isGranted('api:access:full')) {
                throw new AccessDeniedException($this->translator->trans('mautic.core.error.accessdenied', [], 'flashes'));
            }
            $client = $event->getClient();

            if ($client instanceof Client) {
                $event->setAuthorizedClient(
                    $client->isAuthorizedClient($user)
                );
            }
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event): void
    {
        $client = $event->getClient();

        if ($event->isAuthorizedClient() && $client instanceof Client && $user = $this->getUser($event)) {
            $client->addUser($user);
            $this->em->persist($client);
            $this->em->flush();
        }
    }

    /**
     * @return mixed
     */
    private function getUser(OAuthEvent $event)
    {
        return $this->userRepository->findOneByUsername($event->getUser()->getUserIdentifier());
    }
}
