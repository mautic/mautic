<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Validator\Constraints\NotWeak;
use PHPUnit\Framework\Assert;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasswordStrengthEstimatorModelTest extends MauticMysqlTestCase
{
    private PasswordHasherFactoryInterface $passwordHasher;

    private RoleRepository $roleRepository;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHasher = self::getContainer()->get('security.password_hasher_factory');
        $this->roleRepository = $this->em->getRepository(Role::class);
        $this->validator      = static::getContainer()->get('validator');
    }

    public function testThatItIsNotPossibleToCreateAnUserWithAWeakPassword(): void
    {
        $simplePassword = '11111111';

        $user = new User();
        $user->setFirstName('First Name');
        $user->setLastName('LastName');
        $user->setUsername('username');
        $user->setEmail('some@email.domain');
        $user->setPlainPassword($simplePassword);
        $user->setPassword($this->passwordHasher->getPasswordHasher($user)->hash($simplePassword));
        $user->setRole($this->roleRepository->findAll()[0]);
        $violations                    = $this->validator->validate($user);
        $hasNotWeakConstraintViolation = false;

        /** @var ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $hasNotWeakConstraintViolation |= $violation->getConstraint() instanceof NotWeak;
        }

        Assert::assertGreaterThanOrEqual(1, count($violations));
        Assert::assertTrue((bool) $hasNotWeakConstraintViolation);
    }
}
