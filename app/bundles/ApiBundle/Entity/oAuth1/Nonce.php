<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Mautic\ApiBundle\Entity\oAuth1\NonceRepository")
 * @ORM\Table(name="oauth1_nonces")
 */
class Nonce
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $nonce;

    /**
     * @ORM\Column(type="string")
     */
    private $timestamp;

    /**
     * @param $nonce
     * @param $timestamp
     */
    public function __construct($nonce, $timestamp)
    {
        $this->nonce     = $nonce;
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
