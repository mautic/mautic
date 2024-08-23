<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\EmailBundle\Entity\Email;
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
    private MockObject $emailModel;

    private EmailActionModel $emailActionModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModel       = $this->createMock(EmailModel::class);
        $this->emailActionModel = new EmailActionModel($this->emailModel);
    }

    /**
     * @test
     */
    public function testSetsNewCategoryForEditableEmails(): void
    {
        $oldCategory = new Category();
        $oldCategory->setTitle(self::OLD_CATEGORY_TITLE);

        $newCategory = new Category();
        $newCategory->setTitle(self::NEW_CATEGORY_TITLE);

        $emails = $this->buildEmailsWithCategory($oldCategory, 3);
        $this->configureEmailModelToEdit($emails);

        $this->emailActionModel->setCategory(
            array_map(fn (Email $email) => $email->getId(), $emails),
            $newCategory
        );

        foreach ($emails as $email) {
            $this->assertEquals($email->getCategory(), $newCategory);
        }
    }

    /**
     * @test
     */
    public function testDoesntSetNewCategoryForNonEditableEmails(): void
    {
        $oldCategory = new Category();
        $oldCategory->setTitle(self::OLD_CATEGORY_TITLE);

        $newCategory = new Category();
        $newCategory->setTitle(self::NEW_CATEGORY_TITLE);

        $emails = $this->buildEmailsWithCategory($oldCategory, 5);
        $this->configureEmailModelToNotEdit($emails);

        $this->emailActionModel->setCategory(
            array_map(fn (Email $email) => $email->getId(), $emails),
            $newCategory
        );

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

    private function configureEmailModelToEdit(array $emails): void
    {
        $this
            ->emailModel
            ->method('getByIds')
            ->with(
                array_map(fn (Email $email) => $email->getId(), $emails)
            )
            ->willReturn($emails);

        $this
            ->emailModel
            ->method('canEdit')
            ->willReturn(true);

        $this
            ->emailModel
            ->expects($this->once())
            ->method('saveEntities')
            ->with($emails);
    }

    private function configureEmailModelToNotEdit(array $emails): void
    {
        $this
            ->emailModel
            ->method('getByIds')
            ->with(
                array_map(fn (Email $email) => $email->getId(), $emails)
            )
            ->willReturn($emails);

        $this
            ->emailModel
            ->method('canEdit')
            ->willReturn(false);
    }
}
