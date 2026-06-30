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
        $requestStack          = $this->createStub(RequestStack::class);
        $twigMock              = $this->createStub(Environment::class);
        $themeHelper           = $this->createStub(ThemeHelperInterface::class);
        $formActionModel       = $this->createStub(ActionModel::class);
        $formFieldModel        = $this->createStub(FieldModel::class);
        $fieldHelper           = $this->createStub(FormFieldHelper::class);
        $primaryCompanyHelper  = $this->createStub(PrimaryCompanyHelper::class);
        $leadFieldModel        = $this->createStub(LeadFieldModel::class);
        $formUploaderMock      = $this->createMock(FormUploader::class);
        $contactTracker        = $this->createStub(ContactTracker::class);
        $columnSchemaHelper    = $this->createStub(ColumnSchemaHelper::class);
        $tableSchemaHelper     = $this->createStub(TableSchemaHelper::class);
        $entityManager         = $this->createMock(EntityManagerInterface::class);
        $dispatcher            = $this->createMock(EventDispatcher::class);
        $formRepository        = $this->createMock(FormRepository::class);
        $form                  = $this->createMock(Form::class);
        $mappedObjectCollector = $this->createStub(MappedObjectCollectorInterface::class);
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
            $this->createStub(CorePermissions::class),
            $dispatcher,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class)
        );
        $matcher = $this->exactly(2);

        $dispatcher->expects($matcher)
            ->method('hasListeners')->willReturnCallback(function (...$parameters) use ($matcher): false {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.form_pre_delete', $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.form_post_delete', $parameters[0]);
                }

                return false;
            });

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
