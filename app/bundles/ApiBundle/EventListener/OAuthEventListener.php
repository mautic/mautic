<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use FOS\OAuthServerBundle\Event\OAuthEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->em             = $factory->getEntityManager();
        $this->mauticSecurity = $factory->getSecurity();
        $this->translator     = $factory->getTranslator();
    }

    /**
     * @param OAuthEvent $event
     *
     * @return void
     * @throws AccessDeniedException
     */
    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        if ($user = $this->getUser($event)) {
            //check to see if user has api access
            if (!$this->mauticSecurity->isGranted("api:access:full")) {
                throw new AccessDeniedException($this->translator->trans('mautic.core.error.accessdenied', array(), 'flashes'));
            }
            $client = $event->getClient();
            $event->setAuthorizedClient(
                $client->isAuthorizedClient($user, $this->em)
            );
        }
    }

    /**
     * @param OAuthEvent $event
     *
     * @return void
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
