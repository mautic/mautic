<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Update\Github;

class Metadata
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $stability;

    /**
     * @var string
     */
    private $minSupportedPHPVersion;

    /**
     * @var string
     */
    private $maxSupportedPHPVersion;

    /**
     * @var string
     */
    private $minSupportedMauticVersion;

    /**
     * @var string
     */
    private $announcementUrl;

    public function __construct(array $metadata)
    {
        $this->version                   = $metadata['version'];
        $this->stability                 = $metadata['stability'];
        $this->minSupportedPHPVersion    = $metadata['minimum_php_version'];
        $this->maxSupportedPHPVersion    = $metadata['maximum_php_version'];
        $this->minSupportedMauticVersion = $metadata['minimum_mautic_version'];
        $this->announcementUrl           = $metadata['announcement_url'];
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStability(): string
    {
        return $this->stability;
    }

    public function getMinSupportedPHPVersion(): string
    {
        return $this->minSupportedPHPVersion;
    }

    public function getMaxSupportedPHPVersion(): string
    {
        return $this->maxSupportedPHPVersion;
    }

    public function getMinSupportedMauticVersion(): string
    {
        return $this->minSupportedMauticVersion;
    }

    public function getAnnouncementUrl(): string
    {
        return $this->announcementUrl;
    }
}
