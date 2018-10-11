<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class CommonEvent.
 */
class TokenReplacementEvent extends CommonEvent
{
    /**
     * @var CommonEntity|string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @var array
     */
    protected $clickthrough = [];

    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * Whatever the calling code wants to make available to the consumers.
     *
     * @var mixed
     */
    protected $passthrough;

    /**
     * TokenReplacementEvent constructor.
     *
     * @param       $content
     * @param null  $lead
     * @param array $clickthrough
     * @param mixed $passthrough
     */
    public function __construct($content, $lead = null, array $clickthrough = [], $passthrough = null)
    {
        if ($content instanceof CommonEntity) {
            $this->entity = $content;
        }

        $this->content      = $content;
        $this->lead         = $lead;
        $this->clickthrough = $clickthrough;
        $this->passthrough  = $passthrough;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return array
     */
    public function getClickthrough()
    {
        if (!in_array('lead', $this->clickthrough)) {
            if (is_array($this->lead) && !empty($this->lead['id'])) {
                $this->clickthrough['lead'] = $this->lead['id'];
            } elseif ($this->lead instanceof Lead && $this->lead->getId()) {
                $this->clickthrough['lead'] = $this->lead->getId();
            }
        }

        return $this->clickthrough;
    }

    /**
     * @param array $clickthrough
     */
    public function setClickthrough($clickthrough)
    {
        $this->clickthrough = $clickthrough;
    }

    /**
     * @return CommonEntity|string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param $token
     * @param $value
     */
    public function addToken($token, $value)
    {
        $this->tokens[$token] = $value;
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @return mixed|null
     */
    public function getPassthrough()
    {
        return $this->passthrough;
    }
}
