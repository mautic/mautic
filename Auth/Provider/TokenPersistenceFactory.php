<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;

class TokenPersistenceFactory
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * @param EntityManager $entityManager
     * @param EncryptionHelper $encryptionHelper
     */
    public function __construct(EntityManager $entityManager, EncryptionHelper $encryptionHelper)
    {
        $this->entityManager = $entityManager;
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * @param Integration $integration
     *
     * @return TokenPersistence
     */
    public function create(Integration $integration)
    {
        $tokenPersistence = new TokenPersistence(
            $this->encryptionHelper,
            $this->entityManager->getRepository(IntegrationEntity::class)
        );

        $tokenPersistence->setIntegration($integration);

        return $tokenPersistence;
    }
}