<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Templating\Helper;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\CoreBundle\Templating\Helper\GravatarHelper;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Templating\Helper\Helper;

class AvatarHelper extends Helper
{
    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var GravatarHelper
     */
    private $gravatarHelper;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @param AssetsHelper   $assetsHelper
     * @param GravatarHelper $gravatarHelper
     * @param PathsHelper    $pathsHelper
     */
    public function __construct(
        AssetsHelper $assetsHelper,
        GravatarHelper $gravatarHelper,
        PathsHelper $pathsHelper
    ) {
        $this->assetsHelper   = $assetsHelper;
        $this->gravatarHelper = $gravatarHelper;
        $this->pathsHelper    = $pathsHelper;
    }

    /**
     * @param Lead $lead
     *
     * @return mixed
     */
    public function getAvatar(Lead $lead)
    {
        $preferred  = $lead->getPreferredProfileImage();
        $socialData = $lead->getSocialCache();
        $leadEmail  = $lead->getEmail();

        if ($preferred == 'custom') {
            $avatarPath = $this->getAvatarPath(true).'/avatar'.$lead->getId();
            if (file_exists($avatarPath) && $fmtime = filemtime($avatarPath)) {
                // Append file modified time to ensure the latest is used by browser
                $img = $this->assetsHelper->getUrl(
                    $this->getAvatarPath().'/avatar'.$lead->getId().'?'.$fmtime,
                    null,
                    null,
                    false,
                    true
                );
            }
        } elseif (isset($socialData[$preferred]) && !empty($socialData[$preferred]['profile']['profileImage'])) {
            $img = $socialData[$preferred]['profile']['profileImage'];
        }

        if (empty($img)) {
            // Default to gravatar if others failed
            if (!empty($leadEmail)) {
                $img = $this->gravatarHelper->getImage($leadEmail);
            } else {
                $img = $this->getDefaultAvatar();
            }
        }

        return $img;
    }

    /**
     * Get avatar path.
     *
     * @param string $absolute
     *
     * @return string
     */
    public function getAvatarPath($absolute = false)
    {
        $imageDir = $this->pathsHelper->getSystemPath('images', $absolute);

        return $imageDir.'/lead_avatars';
    }

    /**
     * @param bool|false $absolute
     *
     * @return mixed
     */
    public function getDefaultAvatar($absolute = false)
    {
        $img = $this->pathsHelper->getSystemPath('assets').'/images/avatar.png';

        return UrlHelper::rel2abs($this->assetsHelper->getUrl($img));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_avatar';
    }
}
