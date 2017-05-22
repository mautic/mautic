<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Test;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Doctrine\Helper\SchemaHelperFactory;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class TestFormModel extends WebTestCase
{
    private $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    public function testGetContent()
    {
        $fieldSession          = 'mautic_'.sha1(uniqid(mt_rand(), true));
        $fields[$fieldSession] =
            [
                'label'        => 'name',
                'showLabel'    => 1,
                'saveResult'   => 1,
                'defaultValue' => false,
                'alias'        => 'f_name',
                'id'           => $fieldSession,
            ];

        $form = new Form();

        $formModel = $this->getModel();
        $formModel->setFields($form, $fields);
        $entityFields = $form->getFields()->toArray();
        $this->assertInstanceOf(Field::class, $entityFields[$fieldSession]);
    }

    public function testGetComponentsFields()
    {
        $formModel  = $this->getModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('fields', $components);
    }

    public function testGetComponentsActions()
    {
        $formModel  = $this->getModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('actions', $components);
    }

    public function testGetComponentsChoices()
    {
        $formModel  = $this->getModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('choices', $components);
    }

    public function testGetComponentsValidators()
    {
        $formModel  = $this->getModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('validators', $components);
    }

    protected function getModel()
    {
        $requestStack         = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $templatingHelperMock = $this->getMockBuilder(TemplatingHelper::class)->disableOriginalConstructor()->getMock();
        $themeHelper          = $this->getMockBuilder(ThemeHelper::class)->disableOriginalConstructor()->getMock();
        $schemaHelperFactory  = $this->getMockBuilder(SchemaHelperFactory::class)->disableOriginalConstructor()->getMock();
        $formActionModel      = $this->getMockBuilder(ActionModel::class)->disableOriginalConstructor()->getMock();
        $formFieldModel       = $this->getMockBuilder(FieldModel::class)->disableOriginalConstructor()->getMock();
        $leadModel            = $this->getMockBuilder(LeadModel::class)->disableOriginalConstructor()->getMock();
        $fieldHelper          = $this->getMockBuilder(FormFieldHelper::class)->disableOriginalConstructor()->getMock();
        $leadFieldModel       = $this->getMockBuilder('\Mautic\LeadBundle\Model\FieldModel')->disableOriginalConstructor()->getMock();

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()
            ->getMock();

        $leadModel->expects($this->any())->method('getCurrentLead')
            ->willReturn(new Lead());

        $templatingHelperMock->expects($this->any())->method('getTemplating')
            ->willReturn($this->container->get('templating'));

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formRepository = $this->getMockBuilder(FormRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticFormBundle:Form', $formRepository],
                    ]
                )
            );

        $formModel = new FormModel(
            $requestStack,
            $templatingHelperMock,
            $themeHelper,
            $schemaHelperFactory,
            $formActionModel,
            $formFieldModel,
            $leadModel,
            $fieldHelper,
            $leadFieldModel
        );

        $formModel->setDispatcher($dispatcher);
        $formModel->setTranslator($translator);
        $formModel->setEntityManager($entityManager);

        return $formModel;
    }
}
