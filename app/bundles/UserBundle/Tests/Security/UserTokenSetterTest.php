<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class UserTokenSetterTest extends AbstractMauticTestCase
{
    public function testSetUserMakesTheUserAvailableToUserHelper(): void
    {
        /** @var MockObject&UserModel $userModel */
        $userModel = $this->createMock(UserModel::class);
        $user      = new User();

        $userModel->method('getEntity')
            ->with(1)
            ->willReturn($user);

        $userTokenSetter = new UserTokenSetter($userModel, $this->getContainer()->get(TokenStorageInterface::class));

        $userTokenSetter->setUser(1);

        /** @var UserHelper $userHelper */
        $userHelper = $this->getContainer()->get(UserHelper::class);

        $this->assertSame($user, $userHelper->getUser());
    }
}
