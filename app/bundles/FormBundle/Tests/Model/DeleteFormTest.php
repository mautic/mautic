<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Collector\MappedObjectCollectorInterface;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class DeleteFormTest extends \PHPUnit\Framework\TestCase
{
    public function testDelete(): void
    {
        $requestStack          = $this->createMock(RequestStack::class);
        $twigMock              = $this->createMock(Environment::class);
        $themeHelper           = $this->createMock(ThemeHelperInterface::class);
        $formActionModel       = $this->createMock(ActionModel::class);
        $formFieldModel        = $this->createMock(FieldModel::class);
        $fieldHelper           = $this->createMock(FormFieldHelper::class);
        $primaryCompanyHelper  = $this->createMock(PrimaryCompanyHelper::class);
        $leadFieldModel        = $this->createMock(LeadFieldModel::class);
        $formUploaderMock      = $this->createMock(FormUploader::class);
        $contactTracker        = $this->createMock(ContactTracker::class);
        $columnSchemaHelper    = $this->createMock(ColumnSchemaHelper::class);
        $tableSchemaHelper     = $this->createMock(TableSchemaHelper::class);
        $entityManager         = $this->createMock(EntityManagerInterface::class);
        $dispatcher            = $this->createMock(EventDispatcher::class);
        $formRepository        = $this->createMock(FormRepository::class);
        $form                  = $this->createMock(Form::class);
        $mappedObjectCollector = $this->createMock(MappedObjectCollectorInterface::class);
        $formModel             = new FormModel(
            $requestStack,
            $twigMock,
            $themeHelper,
            $formActionModel,
            $formFieldModel,
            $fieldHelper,
            $primaryCompanyHelper,
            $leadFieldModel,
            $formUploaderMock,
            $contactTracker,
            $columnSchemaHelper,
            $tableSchemaHelper,
            $mappedObjectCollector,
            $entityManager,
            $this->createMock(CorePermissions::class),
            $dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );

        $dispatcher->expects($this->exactly(2))
            ->method('hasListeners')
            ->withConsecutive(['mautic.form_pre_delete'], ['mautic.form_post_delete'])
            ->willReturn(false);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($formRepository);

        $form->expects($this->exactly(2))
            ->method('getId')
            ->with()
            ->willReturn(1);

        $formUploaderMock->expects($this->once())
            ->method('deleteFilesOfForm')
            ->with($form);

        $formRepository->expects($this->once())
            ->method('deleteEntity')
            ->with($form);

        $formModel->deleteEntity($form);

        $this->assertSame(1, $form->deletedId);
    }
}
