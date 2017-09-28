<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Helper\SchemaHelperFactory;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\PageModel;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class FormTestAbstract extends WebTestCase
{
    protected static $mockId   = 123;
    protected static $mockName = 'Mock test name';
    protected $mockTrackingId;
    protected $container;

    protected function setUp()
    {
        self::bootKernel();
        $this->mockTrackingId = hash('sha1', uniqid(mt_rand()));
        $this->container      = self::$kernel->getContainer();
    }

    /**
     * @return FormModel
     */
    protected function getFormModel()
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
            ->getMockBuilder(ThemeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schemaHelperFactory = $this
            ->getMockBuilder(SchemaHelperFactory::class)
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

        $leadModel = $this
            ->getMockBuilder(LeadModel::class)
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

        $dispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this
            ->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel->expects($this
            ->any())
            ->method('getCurrentLead')
            ->willReturn($this
                ->returnValue(['id' => self::$mockId, 'name' => self::$mockName]));

        $templatingHelperMock->expects($this
            ->any())
            ->method('getTemplating')
            ->willReturn($this->container->get('templating'));

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRepository = $this
            ->getMockBuilder(FormRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this
            ->any())
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

    /**
     * @return SubmissionModel
     */
    protected function getSubmissionModel()
    {
        $ipLookupHelper = $this
            ->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $templatingHelperMock = $this
            ->getMockBuilder(TemplatingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formModel = $this
            ->getMockBuilder(FormModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pageModel = $this
            ->getMockBuilder(PageModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this
            ->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $campaignModel = $this
            ->getMockBuilder(CampaignModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadFieldModel = $this
            ->getMockBuilder(LeadFieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadFieldModel->expects($this
            ->any())->method('getUniqueIdentifierFields')
            ->willReturn(['eyJpc1B1Ymxpc2hlZCI6dHJ1ZSwiaXNVbmlxdWVJZGVudGlmZXIiOnRydWUsIm9iamVjdCI6ImxlYWQifQ==' => ['email' => 'Email']]);

        $companyModel = $this
            ->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldHelper = $this
            ->getMockBuilder(FormFieldHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this
            ->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel->expects($this
            ->any())->method('getTrackingCookie')
            ->willReturn([$this->mockTrackingId, true]);

        $leadModel->expects($this
            ->any())
            ->method('getCurrentLead')
            ->with($this->logicalOr(
                false,
                true
            ))
            ->will($this->returnCallback([$this, 'getCurrentLead']));

        $userHelper = $this
            ->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userHelper->expects($this
            ->any())
            ->method('getUser')
            ->willReturn(new User());

        $mockLeadField['email'] = [
                'label'        => 'Email',
                'alias'        => 'email',
                'type'         => 'email',
                'group'        => 'core',
                'group_label'  => 'Core',
                'defaultValue' => '',
                'properties'   => [],
            ];

        $leadFieldModel->expects($this
            ->any())
            ->method('getFieldListWithProperties')
            ->willReturn($mockLeadField);

        $leadFieldModel->expects($this
            ->any())->method('getUniqueIdentiferFields')
            ->willReturn($mockLeadField);

        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRepository = $this->getMockBuilder(FormRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['MauticLeadBundle:Lead', $leadRepository],
                        ['MauticFormBundle:Submission', $formRepository],
                    ]
                )
            );

        $leadRepository->expects($this->any())
            ->method('getLeadsByUniqueFields')
            ->willReturn(null);
        $ipAddress = new IpAddress();
        $ipLookupHelper
            ->expects($this
                ->any())
            ->method('getIpAddress')
            ->willReturn($ipAddress);

        $mockLogger = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $submissionModel = new SubmissionModel(
            $ipLookupHelper,
            $templatingHelperMock,
            $formModel,
            $pageModel,
            $leadModel,
            $campaignModel,
            $leadFieldModel,
            $companyModel,
            $fieldHelper
        );

        $submissionModel->setDispatcher($dispatcher);
        $submissionModel->setTranslator($translator);
        $submissionModel->setEntityManager($entityManager);
        $submissionModel->setUserHelper($userHelper);
        $submissionModel->setLogger($mockLogger);

        return $submissionModel;
    }

    public function getTestFormFields()
    {
        $fieldSession          = 'mautic_'.sha1(uniqid(mt_rand(), true));
        $fields[$fieldSession] =
            [
                'label'        => 'Email',
                'showLabel'    => 1,
                'saveResult'   => 1,
                'defaultValue' => false,
                'alias'        => 'email',
                'type'         => 'email',
                'leadField'    => 'email',
                'id'           => $fieldSession,
            ];

        return $fields;
    }

    public function getCurrentLead($tracking)
    {
        return $tracking ? [new Lead(), $this->mockTrackingId, true] : new Lead();
    }
}
