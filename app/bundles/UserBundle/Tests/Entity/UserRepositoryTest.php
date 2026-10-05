<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): UserRepository
    {
        $repository = $this->configureRepository(User::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.user.user.searchcommand.neverloggedin' => 'is:never_logged_in',
            default                                       => $id,
        });
        $repository->autowireCommonRepository($translator);

        return $repository;
    }

    public function testSearchCommandsContainNeverLoggedInFilter(): void
    {
        $this->assertContains('mautic.user.user.searchcommand.neverloggedin', $this->getRepository()->getSearchCommands());
    }
}
