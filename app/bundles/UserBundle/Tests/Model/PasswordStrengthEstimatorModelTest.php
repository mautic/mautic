<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Validator\Constraints\NotWeak;
use PHPUnit\Framework\Assert;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordStrengthEstimatorModelTest extends MauticMysqlTestCase
{
    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    protected function setUp()
    {
        parent::setUp();
        $this->passwordEncoder = $this->getContainer()->get('security.encoder_factory');
        $this->roleRepository  = $this->em->getRepository('MauticUserBundle:Role');
        $this->validator       = $this->getContainer()->get('validator');
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
        $user->setPassword($this->passwordEncoder->getEncoder($user)->encodePassword($simplePassword, $user->getSalt()));
        $user->setRole($this->roleRepository->findAll()[0]);
        $violations                    = $this->validator->validate($user);
        $hasNotWeakConstraintViolation = false;

        foreach ($violations as $violation) {
            $hasNotWeakConstraintViolation |= $violation->getConstraint() instanceof NotWeak;
        }

        Assert::assertGreaterThanOrEqual(1, count($violations));
        Assert::assertTrue((bool) $hasNotWeakConstraintViolation);
    }
}
