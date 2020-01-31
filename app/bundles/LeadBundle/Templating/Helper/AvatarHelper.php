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

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Exception\ContactNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\Helper\Helper;

class AvatarHelper extends Helper
{
    private $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    public function __construct(
        AssetsHelper $assetsHelper,
        PathsHelper $pathsHelper
    ) {
        $this->assetsHelper = $assetsHelper;
        $this->pathsHelper  = $pathsHelper;
    }

    /**
     * @param Lead   $lead
     * @param string $filePath
     *
     * @throws FileNotFoundException
     */
    public function createAvatarFromFile(Lead $lead = null, $filePath)
    {
        if (!$lead) {
            throw new ContactNotFoundException();
        }

        if (!file_exists($filePath)) {
            throw new FileNotFoundException();
        }

        $avatarDir = $this->getAvatarPath(true);

        if (!file_exists($avatarDir)) {
            mkdir($avatarDir);
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->imageTypes)) {
            throw new \Exception('File is not image');
        }

        $fs = new Filesystem();
        $fs->copy($filePath, $avatarDir.DIRECTORY_SEPARATOR.'avatar'.$lead->getId(), true);
    }

    /**
     * @return mixed
     */
    public function getAvatar(Lead $lead)
    {
        $preferred  = $lead->getPreferredProfileImage();
        $socialData = $lead->getSocialCache();

        if ('custom' == $preferred) {
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
            $img = $this->getDefaultAvatar();
        }

        return $img;
    }

    /**
     * Get avatar path.
     *
     * @param bool $absolute
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
