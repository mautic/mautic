<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

/**
 * Class OptionsDTO.
 */
final class OptionsDTO implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $startTime = null;

    /**
     * @var bool|null
     */
    private $openTracking = null;

    /**
     * @var bool|null
     */
    private $clickTracking = null;

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        $json = [];
        if ($this->startTime !== null) {
            $json['start_time'] = $this->startTime;
        }
        if ($this->openTracking !== null) {
            $json['open_tracking'] = $this->openTracking;
        }
        if ($this->clickTracking !== null) {
            $json['click_tracking'] = $this->clickTracking;
        }

        return $json;
    }

    /**
     * @param null|string $startTime
     *
     * @return OptionsDTO
     */
    public function setStartTime($startTime = null)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * @param bool|null $openTracking
     *
     * @return OptionsDTO
     */
    public function setOpenTracking($openTracking = null)
    {
        $this->openTracking = $openTracking;

        return $this;
    }

    /**
     * @param bool|null $clickTracking
     *
     * @return OptionsDTO
     */
    public function setClickTracking($clickTracking = null)
    {
        $this->clickTracking = $clickTracking;

        return $this;
    }
}
