<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Tests\Traits\CreateEntityTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserTextComparisonFunctionalTest extends MauticMysqlTestCase
{
    use CreateEntityTrait {
        createUser as createUserFromEmail;
    }

    /**
     * @return iterable<string, array{username: string, email: string, identifier: string}>
     */
    public static function loginIdentifierProvider(): iterable
    {
        yield 'exact identifier remains supported' => [
            'username'   => 'ExactLogin',
            'email'      => 'exact-login@example.test',
            'identifier' => 'ExactLogin',
        ];

        yield 'username sharp s folds to ss' => [
            'username'   => 'StraßeLogin',
            'email'      => 'strasse-login@example.test',
            'identifier' => 'STRASSELOGIN',
        ];

        yield 'email case and accent are ignored' => [
            'username'   => 'jose-login',
            'email'      => 'José.Login@example.test',
            'identifier' => 'JOSE.LOGIN@EXAMPLE.TEST',
        ];
    }

    #[DataProvider('loginIdentifierProvider')]
    public function testLoginIdentifierMatchesIgnoringCaseAndAccents(string $username, string $email, string $identifier): void
    {
        $password = Uuid::uuid4()->toString();
        $role     = new Role();
        $role->setName('Role');
        $this->em->persist($role);

        $this->makeUser($role, $username, $email, $password);
        $this->em->flush();
        $this->em->clear();

        $this->logoutUser();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/login');
        $form    = $crawler->filter('form')->form();
        $form->setValues([
            '_username' => $identifier,
            '_password' => $password,
        ]);

        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Dashboard |', $crawler->filterXPath('//head/title')->text());
    }

    /**
     * @return iterable<string, array{field: 'username'|'email', first: string, duplicate: string}>
     */
    public static function duplicateUserIdentifierProvider(): iterable
    {
        yield 'username exact duplicate control' => ['field' => 'username', 'first' => 'ExactUnique', 'duplicate' => 'ExactUnique'];
        yield 'email exact duplicate control' => ['field' => 'email', 'first' => 'exact.unique@example.test', 'duplicate' => 'exact.unique@example.test'];
        yield 'username case and sharp s' => ['field' => 'username', 'first' => 'StraßeUnique', 'duplicate' => 'STRASSEUNIQUE'];
        yield 'username accent and case' => ['field' => 'username', 'first' => 'JoséUnique', 'duplicate' => 'JOSEUNIQUE'];
        yield 'email case and sharp s' => ['field' => 'email', 'first' => 'Straße.unique@example.test', 'duplicate' => 'STRASSE.UNIQUE@EXAMPLE.TEST'];
        yield 'email accent and case' => ['field' => 'email', 'first' => 'José.unique@example.test', 'duplicate' => 'JOSE.UNIQUE@EXAMPLE.TEST'];
    }

    #[DataProvider('duplicateUserIdentifierProvider')]
    public function testDatabaseUniqueConstraintRejectsCaseAndAccentDuplicates(string $field, string $first, string $duplicate): void
    {
        $role = new Role();
        $role->setName('Unique text comparison role');
        $this->em->persist($role);

        $this->em->persist($this->makeUser($role, 'first-'.$field.'-'.Uuid::uuid4()->toString(), 'first-'.Uuid::uuid4()->toString().'@example.test', 'password'));
        $this->em->flush();

        $this->em->persist($this->makeUser(
            $role,
            'username' === $field ? $first : 'second-'.Uuid::uuid4()->toString(),
            'email' === $field ? $first : 'second-'.Uuid::uuid4()->toString().'@example.test',
            'password'
        ));
        $this->em->flush();

        $duplicateUser = $this->makeUser(
            $role,
            'username' === $field ? $duplicate : 'third-'.Uuid::uuid4()->toString(),
            'email' === $field ? $duplicate : 'third-'.Uuid::uuid4()->toString().'@example.test',
            'password'
        );

        $validationGroups = ['User'];
        if ('username' === $field) {
            $duplicateUser->setPlainPassword(Uuid::uuid4()->toString().'!Ab');
            $validationGroups = ['Default'];
        }

        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($duplicateUser, null, $validationGroups);
        $this->assertCount(1, $violations);
        $violation = $violations->get(0);
        $this->assertNotNull($violation);
        $this->assertSame($field, $violation->getPropertyPath());
        $this->assertSame(UniqueEntity::NOT_UNIQUE_ERROR, $violation->getCode());

        $this->em->persist($duplicateUser);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    private function makeUser(Role $role, string $username, string $email, string $plainPassword): User
    {
        $user = $this->createUserFromEmail($role, $email, $plainPassword);
        $user->setUsername($username);

        return $user;
    }
}
