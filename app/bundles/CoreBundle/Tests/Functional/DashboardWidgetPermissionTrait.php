<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\DashboardBundle\Entity\Widget;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Builds the users and widgets a "does this widget honour viewother" test needs.
 *
 * The widget has to be rendered through the dashboard endpoint rather than by calling the
 * model, because the defects these tests cover were in how the subscribers called the
 * models: a model-level test passes whether or not the flag arrives.
 */
trait DashboardWidgetPermissionTrait
{
    /**
     * Extended permissions put viewown at 2 and viewother at 4, so 2 alone means
     * "only mine" and 6 means "everyone's".
     */
    private function createUserWithPermission(string $name, string $bundle, string $permission, int $bitwise): User
    {
        $role = new Role();
        $role->setName('role_'.$name);
        $role->setIsAdmin(false);
        $this->em->persist($role);

        $entity = new Permission();
        $entity->setBundle($bundle);
        $entity->setName($permission);
        $entity->setRole($role);
        $entity->setBitwise($bitwise);
        $this->em->persist($entity);

        $user = new User();
        $user->setEmail($name.'@mautic-test.com');
        $user->setUsername($name);
        $user->setFirstName($name);
        $user->setLastName('Test');
        $user->setRole($role);

        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $this->em->persist($user);

        return $user;
    }

    private function renderWidgetAsUser(User $user, string $type): string
    {
        // Widget::get() refuses a widget the current user did not create, so each user needs one.
        $widget = new Widget();
        $widget->setName($type);
        $widget->setType($type);
        $widget->setParams(['limit' => 50]);
        $widget->setWidth(100);
        $widget->setHeight(330);
        $widget->setCreatedBy($user);
        $this->em->persist($widget);
        $this->em->flush();

        $this->loginUser($user);
        $this->client->xmlHttpRequest(Request::METHOD_GET, sprintf('/s/dashboard/widget/%s', $widget->getId()));
        $this->assertResponseIsSuccessful();

        $content = (string) $this->client->getResponse()->getContent();
        $this->assertJson($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('widgetHtml', $data);

        return (string) $data['widgetHtml'];
    }
}
