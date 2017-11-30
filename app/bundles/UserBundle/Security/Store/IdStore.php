<?php

/*
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Store;

use Doctrine\Common\Persistence\ObjectManager;
use LightSaml\Provider\TimeProvider\TimeProviderInterface;
use LightSaml\Store\Id\IdStoreInterface;
use Mautic\UserBundle\Entity\IdEntry;

class IdStore implements IdStoreInterface
{
    /** @var ObjectManager */
    private $manager;

    /** @var TimeProviderInterface */
    private $timeProvider;

    /**
     * @param ObjectManager         $manager
     * @param TimeProviderInterface $timeProvider
     */
    public function __construct(ObjectManager $manager, TimeProviderInterface $timeProvider)
    {
        $this->manager      = $manager;
        $this->timeProvider = $timeProvider;
    }

    /**
     * @param string    $entityId
     * @param string    $id
     * @param \DateTime $expiryTime
     */
    public function set($entityId, $id, \DateTime $expiryTime)
    {
        $idEntry = $this->manager->find(IdEntry::class, ['entityId' => $entityId, 'id' => $id]);
        if (null == $idEntry) {
            $idEntry = new IdEntry();
        }
        $idEntry->setEntityId($entityId)
                ->setId($id)
                ->setExpiryTime($expiryTime);
        $this->manager->persist($idEntry);
        $this->manager->flush($idEntry);
    }

    /**
     * @param string $entityId
     * @param string $id
     *
     * @return bool
     */
    public function has($entityId, $id)
    {
        /** @var IdEntry $idEntry */
        $idEntry = $this->manager->find(IdEntry::class, ['entityId' => $entityId, 'id' => $id]);
        if (null == $idEntry) {
            return false;
        }
        if ($idEntry->getExpiryTime()->getTimestamp() < $this->timeProvider->getTimestamp()) {
            return false;
        }

        return true;
    }
}
