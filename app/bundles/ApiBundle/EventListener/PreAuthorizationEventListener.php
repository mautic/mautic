<?php

namespace Mautic\ApiBundle\EventListener;

use Doctrine\ORM\EntityManager;
use FOS\OAuthServerBundle\Event\PreAuthorizationEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreAuthorizationEventListener
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $mauticSecurity;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * PreAuthorizationEventListener constructor.
     */
    public function __construct(EntityManager $entityManager, CorePermissions $corePermissions, TranslatorInterface $translator)
    {
        $this->em             = $entityManager;
        $this->mauticSecurity = $corePermissions;
        $this->translator     = $translator;
    }

    /**
     * @throws AccessDeniedException
     */
    public function onPreAuthorizationProcess(PreAuthorizationEvent $event)
    {
        if ($user = $this->getUser($event)) {
            // check to see if user has api access
            if (!$this->mauticSecurity->isGranted('api:access:full')) {
                throw new AccessDeniedException($this->translator->trans('mautic.core.error.accessdenied', [], 'flashes'));
            }
            $client = $event->getClient();
            $event->setAuthorizedClient(
                $client->isAuthorizedClient($user, $this->em)
            );
        }
    }

    public function onPostAuthorizationProcess(PreAuthorizationEvent $event)
    {
        if ($event->isAuthorizedClient()) {
            if (null !== $client = $event->getClient()) {
                $user = $this->getUser($event);
                $client->addUser($user);
                $this->em->persist($client);
                $this->em->flush();
            }
        }
    }

    /**
     * @return mixed
     */
    protected function getUser(PreAuthorizationEvent $event)
    {
        return $this->em->getRepository(\Mautic\UserBundle\Entity\User::class)->findOneByUsername($event->getUser()->getUsername());
    }
}
