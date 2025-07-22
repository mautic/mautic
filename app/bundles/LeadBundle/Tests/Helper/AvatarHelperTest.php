<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

class AvatarHelperTest extends \PHPUnit\Framework\TestCase
{
    private AssetsHelper $assetsHelperMock;

    /**
     * @var MockObject&PathsHelper
     */
    private MockObject $pathsHelperMock;

    private GravatarHelper $gravatarHelperMock;

    private DefaultAvatarHelper $defaultAvatarHelperMock;

    /**
     * @var MockObject&Lead
     */
    private MockObject $leadMock;

    private AvatarHelper $avatarHelper;

    protected function setUp(): void
    {
        $root = realpath(__DIR__.'/../../../../../');

        /** @var Packages&MockObject $packagesMock */
        $packagesMock = $this->createMock(Packages::class);

        /** @var CoreParametersHelper&MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->assetsHelperMock = new AssetsHelper($packagesMock);
        $this->pathsHelperMock  = $this->createMock(PathsHelper::class);
        $this->pathsHelperMock->method('getSystemPath')
        ->willReturn('http://localhost');
        $this->pathsHelperMock->method('getAssetsPath')
          ->willReturn($root.'/app/assets');
        $this->pathsHelperMock->method('getMediaPath')
          ->willReturn($root.'/media');

        $this->assetsHelperMock->setPathsHelper($this->pathsHelperMock);
        $this->defaultAvatarHelperMock = new DefaultAvatarHelper($this->assetsHelperMock);
        $this->gravatarHelperMock      = new GravatarHelper($this->defaultAvatarHelperMock, $coreParametersHelper, $this->createMock(RequestStack::class));
        $this->leadMock                = $this->createMock(Lead::class);
        $this->avatarHelper            = new AvatarHelper($this->assetsHelperMock, $this->pathsHelperMock, $this->gravatarHelperMock, $this->defaultAvatarHelperMock);
    }

    /**
     * Test to get gravatar.
     */
    public function testGetAvatarWhenGravatar(): void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_PORT']     = '80';
        $_SERVER['SERVER_NAME']     = 'localhost';
        $_SERVER['REQUEST_URI']     = 'localhost';

        $this->leadMock->method('getPreferredProfileImage')
            ->willReturn('gravatar');
        $this->leadMock->method('getSocialCache')
            ->willReturn([]);
        $this->leadMock->method('getEmail')
            ->willReturn('mautic@acquia.com');
        $avatar = $this->avatarHelper->getAvatar($this->leadMock);
        $this->assertSame('https://www.gravatar.com/avatar/96f1b78c73c1ee806cf6a4168fe9bf77?s=250&d=http%3A%2F%2Flocalhost%2Fimages%2Favatar.png', $avatar, 'Gravatar image should be returned');

        unset($_SERVER['SERVER_PROTOCOL']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['REQUEST_URI']);
    }

    /**
     * Test to get default image.
     */
    public function testGetAvatarWhenDefault(): void
    {
        $this->leadMock->method('getPreferredProfileImage')
            ->willReturn('gravatar');
        $this->leadMock->method('getSocialCache')
            ->willReturn([]);
        $this->leadMock->method('getEmail')
            ->willReturn('');
        $avatar = $this->avatarHelper->getAvatar($this->leadMock);

        $this->assertSame('http://localhost/images/avatar.png', $avatar, 'Default image image should be returned');
    }
}
