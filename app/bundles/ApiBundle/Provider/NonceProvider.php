<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Provider;

use Bazinga\OAuthServerBundle\Model\ConsumerInterface;
use Bazinga\OAuthServerBundle\Model\Provider\NonceProviderInterface;
use Doctrine\ORM\EntityManager;
use Mautic\ApiBundle\Entity\oAuth1\Nonce;

/**
 * Class NonceProvider.
 */
class NonceProvider implements NonceProviderInterface
{
    /**
     * @var \Mautic\ApiBundle\Entity\oAuth1\NonceRepository
     */
    protected $repo;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->repo = $em->getRepository('MauticApiBundle:oAuth1\Nonce');
    }

    /**
     * Ensure a unique nonce and appropriate timestamp.
     *
     * @param                                                    $nonce
     * @param                                                    $timestamp
     * @param \Bazinga\OAuthServerBundle\Model\ConsumerInterface $consumer
     *
     * @return bool
     */
    public function checkNonceAndTimestampUnicity($nonce, $timestamp, ConsumerInterface $consumer)
    {
        //For some reason this is checked twice so prevent the second check
        static $nonceChecked = false, $notUsed = false;

        if (!$nonceChecked) {
            //set TZ to UTC for strtotime
            $currentTz = date_default_timezone_get();
            date_default_timezone_set('UTC');

            //make sure timestamp is within 15 minutes
            $lastFifteen = strtotime('-15 minutes');
            if ($timestamp >= $lastFifteen) {
                //make sure nonce has not been used before
                $usedNonces = $this->repo->findBy(['nonce' => $nonce]);

                if (count($usedNonces) === 0) {
                    $notUsed = true;
                }

                //do some clean up while here
                $this->repo->removeOutdatedNonces($lastFifteen);
            }

            //ensure TZ is set back to default
            date_default_timezone_set($currentTz);

            $nonceChecked = true;
        }

        return $notUsed;
    }

    /**
     * @param                                                    $nonce
     * @param                                                    $timestamp
     * @param \Bazinga\OAuthServerBundle\Model\ConsumerInterface $consumer
     *
     * @return bool
     */
    public function registerNonceAndTimestamp($nonce, $timestamp, ConsumerInterface $consumer)
    {
        //For some reason this is called twice so prevent the second register
        static $nonceRegistered = false;

        if (!$nonceRegistered) {
            $nonce = new Nonce($nonce, $timestamp);
            $this->em->persist($nonce);
            $this->em->flush();

            $nonceRegistered = true;
        }
    }
}
