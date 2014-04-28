<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use FOS\OAuthServerBundle\Event\OAuthEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class OAuthEventListener
{

    private $em;
    private $mauticSecurity;
    private $translator;

    public function __construct(EntityManager $em, CorePermissions $mauticSecurity, $translator)
    {
        if (!$translator instanceof Translator && !$translator instanceof IdentityTranslator) {
            throw new FatalErrorException();
        }

        $this->em             = $em;
        $this->mauticSecurity = $mauticSecurity;
        $this->translator     = $translator;
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        if ($user = $this->getUser($event)) {
            //check to see if user has api access
            if (!$this->mauticSecurity->isGranted("api:access:full")) {
                throw new AccessDeniedException($this->translator->trans('mautic.core.accessdenied'));
            }

            $event->setAuthorizedClient(
                $user->isAuthorizedClient($event->getClient(), $this->em)
            );
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        if ($event->isAuthorizedClient()) {
            if (null !== $client = $event->getClient()) {
                $user = $this->getUser($event);

                //add the client to the user
                $user->addClient($client);
                $this->em->persist($user);

                $this->em->flush();
            }
        }
    }

    protected function getUser(OAuthEvent $event)
    {
        return $this->em->getRepository('MauticUserBundle:User')->findOneByUsername($event->getUser()->getUsername());
    }
}