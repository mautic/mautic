<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Provider;

use Bazinga\OAuthServerBundle\Model\ConsumerInterface;
use Bazinga\OAuthServerBundle\Model\Provider\NonceProviderInterface;

class NonceProvider implements NonceProviderInterface
{
    /**
     * @param $nonce
     * @param $timestamp
     * @param  \Bazinga\OAuthServerBundle\Model\ConsumerInterface $consumer
     * @return boolean
     */
    public function checkNonceAndTimestampUnicity($nonce, $timestamp, ConsumerInterface $consumer)
    {

    }

    /**
     * @param $nonce
     * @param $timestamp
     * @param  \Bazinga\OAuthServerBundle\Model\ConsumerInterface $consumer
     * @return boolean
     */
    public function registerNonceAndTimestamp($nonce, $timestamp, ConsumerInterface $consumer)
    {

    }
}