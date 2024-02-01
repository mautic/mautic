<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Filesystem\Filesystem;

final class AvatarHelper
{
    /**
     * @var array<string>
     */
    private array $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];

    public function __construct(
        private AssetsHelper $assetsHelper,
        private PathsHelper $pathsHelper,
        private GravatarHelper $gravatarHelper,
        private DefaultAvatarHelper $defaultAvatarHelper
    ) {
    }

    /**
     * @param string $filePath
     *
     * @throws FileNotFoundException
     */
    public function createAvatarFromFile(Lead $lead, $filePath): void
    {
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
        $leadEmail  = $lead->getEmail();

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
            // Default to gravatar if others failed
            if (!empty($leadEmail)) {
                $img = $this->gravatarHelper->getImage($leadEmail);
            } else {
                $img = $this->defaultAvatarHelper->getDefaultAvatar();
            }
        }

        return $img;
    }

    /**
     * Get avatar path.
     *
     * @param bool $absolute
     */
    public function getAvatarPath($absolute = false): string
    {
        $imageDir = $this->pathsHelper->getSystemPath('images', $absolute);

        return $imageDir.'/lead_avatars';
    }

    /**
     * @deprecated Use DefaultAvatarHelper::getDefaultAvatar instead of it
     *
     * @param bool|false $absolute
     */
    public function getDefaultAvatar(bool $absolute = false): string
    {
        return $this->defaultAvatarHelper->getDefaultAvatar($absolute);
    }

    public function getName(): string
    {
        return 'lead_avatar';
    }
}
