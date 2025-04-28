<?php

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\LanguageExtension;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LanguageExtensionTest extends TestCase
{
    public function testGetLanguageNameReturnsEnglishForEn(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);
        $extension = new LanguageExtension($security);
        $this->assertEquals('English', $extension->getLanguageName('en'));
    }

    public function testGetLanguageNameReturnsCodeOnException(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);
        $extension = new LanguageExtension($security);
        $this->assertEquals('xx', $extension->getLanguageName('xx'));
    }

    public function testGetLanguageNameUsesUserLocale(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getLocale')->willReturn('fr');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $extension = new LanguageExtension($security);
        $this->assertEquals('anglais', $extension->getLanguageName('en'));
    }
}
