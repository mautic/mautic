<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Model\EmailActionModel;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailActionModelTest extends TestCase
{
    public const NEW_CATEGORY_TITLE = 'New category';
    public const OLD_CATEGORY_TITLE = 'Old category';

    /**
     * @var MockObject&EmailModel
     */
    private MockObject $emailModelMock;

    /**
     * @var MockObject&EmailRepository
     */
    private MockObject $emailRepositoryMock;

    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $corePermissionsMock;

    private EmailActionModel $emailActionModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModelMock      = $this->createMock(EmailModel::class);
        $this->emailRepositoryMock = $this->createMock(EmailRepository::class);
        $this->corePermissionsMock = $this->createMock(CorePermissions::class);
        $this->emailActionModel    = new EmailActionModel(
            $this->emailModelMock,
            $this->emailRepositoryMock,
            $this->corePermissionsMock
        );
    }

    public function testSetsNewCategoryForEditableEmails(): void
    {
        $oldCategory = new Category();
        $oldCategory->setTitle(self::OLD_CATEGORY_TITLE);

        $newCategory = new Category();
        $newCategory->setTitle(self::NEW_CATEGORY_TITLE);

        $emails = $this->buildEmailsWithCategory($oldCategory, 3);
        $this->configureRepositoryToReturn($emails);
        $this->configurePermissionToAllowEdition(true);
        $this->configureModelToSave($emails);

        $this->tryToSetCategory($emails, $newCategory);

        foreach ($emails as $email) {
            $this->assertEquals($email->getCategory(), $newCategory);
        }
    }

    public function testDoesntSetNewCategoryForNonEditableEmails(): void
    {
        $oldCategory = new Category();
        $oldCategory->setTitle(self::OLD_CATEGORY_TITLE);

        $newCategory = new Category();
        $newCategory->setTitle(self::NEW_CATEGORY_TITLE);

        $emails = $this->buildEmailsWithCategory($oldCategory, 5);
        $this->configureRepositoryToReturn($emails);
        $this->configurePermissionToAllowEdition(false);
        $this->tryToSetCategory($emails, $newCategory);

        foreach ($emails as $email) {
            $this->assertEquals($email->getCategory(), $oldCategory);
        }
    }

    /**
     * @return array<Email>
     */
    private function buildEmailsWithCategory(Category $category, int $quantity): array
    {
        $emails = [];

        for ($i = 0; $i < $quantity; ++$i) {
            $email = new Email();
            $email->setId($i);
            $email->setCategory($category);
            $emails[] = $email;
        }

        return $emails;
    }

    private function configurePermissionToAllowEdition(bool $allow): void
    {
        $this->corePermissionsMock
            ->method('hasEntityAccess')
            ->willReturn($allow);
    }

    /**
     * @param array<Email> $emails
     */
    protected function configureRepositoryToReturn(array $emails): void
    {
        $this->emailRepositoryMock
            ->method('findBy')
            ->with(
                ['id' => array_map(fn (Email $email) => $email->getId(), $emails)]
            )
            ->willReturn($emails);
    }

    /**
     * @param array<Email> $emails
     */
    protected function configureModelToSave(array $emails): void
    {
        $this->emailModelMock
            ->expects($this->once())
            ->method('saveEntities')
            ->with($emails);
    }

    /**
     * @param array<Email> $emails
     */
    protected function tryToSetCategory(array $emails, Category $newCategory): void
    {
        $this->emailActionModel
            ->setCategory(
                array_map(fn (Email $email) => $email->getId(), $emails),
                $newCategory
            );
    }
}
