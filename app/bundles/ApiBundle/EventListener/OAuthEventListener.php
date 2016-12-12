<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Doctrine\ORM\EntityManager;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

class OAuthEventListener
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
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * OAuthEventListener constructor.
     *
     * @param EntityManager       $entityManager
     * @param CorePermissions     $corePermissions
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $entityManager, CorePermissions $corePermissions, TranslatorInterface $translator)
    {
        $this->em             = $entityManager;
        $this->mauticSecurity = $corePermissions;
        $this->translator     = $translator;
    }

    /**
     * @param OAuthEvent $event
     *
     * @throws AccessDeniedException
     */
    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        if ($user = $this->getUser($event)) {
            //check to see if user has api access
            if (!$this->mauticSecurity->isGranted('api:access:full')) {
                throw new AccessDeniedException($this->translator->trans('mautic.core.error.accessdenied', [], 'flashes'));
            }
            $client = $event->getClient();
            $event->setAuthorizedClient(
                $client->isAuthorizedClient($user, $this->em)
            );
        }
    }

    /**
     * @param OAuthEvent $event
     */
    public function onPostAuthorizationProcess(OAuthEvent $event)
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
     * @param OAuthEvent $event
     *
     * @return mixed
     */
    protected function getUser(OAuthEvent $event)
    {
        return $this->em->getRepository('MauticUserBundle:User')->findOneByUsername($event->getUser()->getUsername());
    }
}
