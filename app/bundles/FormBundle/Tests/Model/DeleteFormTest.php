<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Tests\FormTestAbstract;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteFormTest extends FormTestAbstract
{
    public function testDelete()
    {
        $requestStack = $this
            ->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $templatingHelperMock = $this
            ->getMockBuilder(TemplatingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $themeHelper = $this
            ->getMockBuilder(ThemeHelperInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formActionModel = $this
            ->getMockBuilder(ActionModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formFieldModel = $this
            ->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldHelper = $this
            ->getMockBuilder(FormFieldHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadFieldModel = $this
            ->getMockBuilder(LeadFieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formUploaderMock = $this
            ->getMockBuilder(FormUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contactTracker = $this->createMock(ContactTracker::class);

        $columnSchemaHelper = $this
            ->getMockBuilder(ColumnSchemaHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tableSchemaHelper = $this
            ->getMockBuilder(TableSchemaHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formModel = new FormModel(
            $requestStack,
            $templatingHelperMock,
            $themeHelper,
            $formActionModel,
            $formFieldModel,
            $fieldHelper,
            $leadFieldModel,
            $formUploaderMock,
            $contactTracker,
            $columnSchemaHelper,
            $tableSchemaHelper
        );

        $dispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher->expects($this->at(0))
            ->method('hasListeners')
            ->with('mautic.form_pre_delete')
            ->willReturn(false);

        $dispatcher->expects($this->at(1))
            ->method('hasListeners')
            ->with('mautic.form_post_delete')
            ->willReturn(false);

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRepository = $this
            ->getMockBuilder(FormRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($formRepository);

        $formModel->setDispatcher($dispatcher);
        $formModel->setEntityManager($entityManager);

        $form = $this
            ->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

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
