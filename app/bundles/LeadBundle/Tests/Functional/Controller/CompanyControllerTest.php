<?php

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\EventListener\PatchCompanyLogoSubscriber;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CompanyControllerTest extends MauticMysqlTestCase
{
    protected const ERROR_MESSAGE = 'The logo filename is not valid. Please enter a valid filename';

    protected $useCleanupRollback = false;

    public const USERNAME = 'jhony';

    private string $txtFilePath;

    private string $imageFilePath;

    private string $imageNoContentTypeFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installCustomFieldWithEvent();
    }

    public function testMergeAction(): void
    {
        $this->client->request('GET', '/s/companies/merge/1');
        $clientResponse         = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());
    }

    public function testMergeActionWithoutPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/companies/merge/1');
        $clientResponse         = $this->client->getResponse();
        $this->assertEquals(403, $clientResponse->getStatusCode());
    }

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);

        $this->loginUser(self::USERNAME);
        $this->client->setServerParameter('PHP_AUTH_USER', self::USERNAME);
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

        return $user;
    }

    public function testFormLogoNameValidateFailByNoExist(): void
    {
        if (file_exists($this->txtFilePath)) {
            unlink($this->imageFilePath);
        }
        $this->requestFormToValidate('test.jpg', true, 'The logo file was not found. Please upload the file first.');
    }

    public function testFormLogoNameWrongExtension(): void
    {
        $name    = pathinfo($this->txtFilePath, PATHINFO_BASENAME);
        $this->requestFormToValidate($name, true, 'The logo filename has an invalid extension.');
    }

    public function testFormLogoNameNoContentType(): void
    {
        $name    = pathinfo($this->imageNoContentTypeFilePath, PATHINFO_BASENAME);
        $this->requestFormToValidate($name);
    }

    public function testFormLogoNameValidateSuccess(): void
    {
        $name    = pathinfo($this->imageFilePath, PATHINFO_BASENAME);
        $this->requestFormToValidate($name, false);
    }

    private function requestFormToValidate(string $fileName, bool $contain = true, string $message = ''): void
    {
        $crawler = $this->client->request(
            'GET',
            '/s/companies/new'
        );
        $form                                                                       = $crawler->filter('form[name=company]')->form();
        $dataValues                                                                 = $form->getPhpValues();
        $dataValues['company'][PatchCompanyLogoSubscriber::NEW_FIELD_NAME_ALIAS]    = 'Company mautic';
        if (array_key_exists(PatchCompanyLogoSubscriber::NEW_FIELD_NAME_ALIAS, $dataValues['company'])) {
            $dataValues['company'][PatchCompanyLogoSubscriber::NEW_FIELD_NAME_ALIAS] = $fileName;
        }
        $form->setValues($dataValues);
        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(200, $clientResponse->getStatusCode());

        if (false === $clientResponse->getContent()) {
            return;
        }

        $content = $clientResponse->getContent();
        $message = $message ?: self::ERROR_MESSAGE;
        if ($contain) {
            self::assertStringContainsString($message, $content);
        } else {
            self::assertStringNotContainsString($message, $content);
        }
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('Jhony');
        $user->setLastName('Doe');
        $user->setUsername(self::USERNAME);
        $user->setEmail('john.doe@email.com');
        $encoder = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        $user->setPassword($encoder->hash('mautic'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }

    private function installCustomFieldWithEvent(): void
    {
        /** @var PatchCompanyLogoSubscriber $subscriber */
        $subscriber = self::getContainer()->get(PatchCompanyLogoSubscriber::class);

        // Real command with the right name so the subscriber executes
        $command = new class('doctrine:migrations:migrate') extends Command {
            public function __construct(string $name)
            {
                parent::__construct($name);
            }
        };

        $input  = new ArrayInput([]);
        $output = new BufferedOutput();
        $event  = new ConsoleTerminateEvent($command, $input, $output, 0);

        $subscriber->installCompanyLogoCustomField($event);
        $this->createFiles();
    }

    private function createFiles(): void
    {
        $this->createTxtFile();
        $this->createImageFile();
        $this->createImageWithNoContentType();
    }

    private function createTxtFile(): string
    {
        $basePath = self::getContainer()->getParameter('mautic.application_dir').'/media/logos/';
        $fileName = 'test.txt';
        $path     = $basePath.$fileName;
        if (file_exists($path)) {
            unlink($path);
        }
        $this->createFile($path, 'This is a test txt file.');

        return $this->txtFilePath = $path;
    }

    private function createFile(string $path, string $content): void
    {
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);
    }

    private function createImageFile(): string
    {
        $basePath = self::getContainer()->getParameter('mautic.application_dir').'/media/logos/';
        $fileName = 'test.jpg';
        $path     = $basePath.$fileName;
        if (file_exists($path)) {
            unlink($path);
        }
        $image   = imagecreatetruecolor(100, 100);
        $bgColor = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $bgColor);
        imagejpeg($image, $path);
        imagedestroy($image);

        return $this->imageFilePath = $path;
    }

    private function createImageWithNoContentType(): string
    {
        $basePath = self::getContainer()->getParameter('mautic.application_dir').'/media/logos/';
        $fileName = 'testNoContent.jpg';
        $path     = $basePath.$fileName;
        if (file_exists($path)) {
            unlink($path);
        }
        $svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"
          "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
        <svg width="100" height="100" version="1.1"
             xmlns="http://www.w3.org/2000/svg">
          <circle cx="50" cy="50" r="40" stroke="green"
                  stroke-width="4" fill="yellow" />
        </svg>';
        $this->createFile($path, $svgContent);

        return $this->imageNoContentTypeFilePath = $path;
    }
}
