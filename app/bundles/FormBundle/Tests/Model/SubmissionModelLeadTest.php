<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Test;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Doctrine\Helper\SchemaHelperFactory;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\FormBundle\Model\FieldModel as FormFieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\FormBundle\Tests\FormTestAbstract;
use Mautic\FormBundle\Tests\Helper\EventListener\NewlyCreatedFormSubmissionSubscriber;
use Mautic\FormBundle\Validator\UploadFieldValidator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SubmissionModelLeadTest extends FormTestAbstract
{
    /** @var EventDispatcher */
    protected $dispatcher;

    /** @var Translator */
    protected $translator;

    /** @var EntityManager */
    protected $entityManager;

    /** @var LeadFieldModel */
    protected $leadFieldModel;

    /** @var LeadModel */
    protected $leadModel;

    /** @var FormModel */
    protected $formModel;

    /** @var Lead */
    protected $currentLead;

    /**
     * Tests that a Lead will have its newlyCreated flag set TRUE when
     * receiving an email address for the first time.
     */
    public function testSaveSubmissionNewLeadIsNewlyCreated()
    {
        $request  = new Request();
        $request->setMethod('POST');
        $formData = [
            'var_name_1' => 'value 1',
            'var_name_2' => 'value 2',
            'email'      => 'test@example.com',
            'submit'     => '',
            'formId'     => 1,
            'return'     => '',
            'formName'   => 'testform',
            'formid'     => 1,
        ];
        $post       = $formData;
        $server     = $request->server->all();
        $form       = new Form();
        $fields     = $this->getTestFormFields();
        $formModel  = $this->getFormModel();
        $formModel->setFields($form, $fields);
        $subscriber = new NewlyCreatedFormSubmissionSubscriber();

        $this->resetCurrentLead();
        $this->getDispatcher()->addSubscriber($subscriber);
        $this->getSubmissionModel()->saveSubmission($post, $server, $form, $request);
        $this->getDispatcher()->removeSubscriber($subscriber);

        // Assert that the lead WAS newlyCreated at the end of the form submission
        $this->assertTrue($subscriber->getNewlyCreated());
    }

    /**
     * Tests that a Lead's newlyCreated flag will remain FALSE when submitted
     * an email address having already had one.
     */
    public function testSaveSubmissionOldLeadIsNotNewlyCreated()
    {
        $request = new Request();
        $request->setMethod('POST');
        $formData = [
            'var_name_1' => 'value 1',
            'var_name_2' => 'value 2',
            'email'      => 'test@example.com',
            'submit'     => '',
            'formId'     => 1,
            'return'     => '',
            'formName'   => 'testform',
            'formid'     => 1,
        ];
        $post      = $formData;
        $server    = $request->server->all();
        $form      = new Form();
        $fields    = $this->getTestFormFields();
        $formModel = $this->getFormModel();
        $formModel->setFields($form, $fields);
        $subscriber = new NewlyCreatedFormSubmissionSubscriber();

        $this->resetCurrentLead();
        $this->currentLead->setEmail('old@example.com');
        $this->getDispatcher()->addSubscriber($subscriber);
        $this->getSubmissionModel()->saveSubmission($post, $server, $form, $request);
        $this->getDispatcher()->removeSubscriber($subscriber);

        // Assert that the lead WAS NOT newlyCreated at the end of the form submission
        $this->assertFalse($subscriber->getNewlyCreated());
    }

    /**
     * Get form model.
     *
     * @return FormModel
     */
    protected function getFormModel()
    {
        if ($this->formModel) {
            return $this->formModel;
        }

        $requestStack = $this
            ->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $templatingHelperMock = $this->getTemplatingHelperMock();

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
            ->getMockBuilder(FormFieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this->getLeadModel();

        $fieldHelperMock = $this->getFieldHelperMock();

        $leadFieldModel = $this->getLeadFieldModel();

        $formUploaderMock = $this->getFormUploaderMock();

        $formModel = new FormModel(
            $requestStack,
            $templatingHelperMock,
            $themeHelper,
            $schemaHelperFactory,
            $formActionModel,
            $formFieldModel,
            $leadModel,
            $fieldHelperMock,
            $leadFieldModel,
            $formUploaderMock
        );

        $dispatcher    = $this->getDispatcher();
        $translator    = $this->getTranslator();
        $entityManager = $this->getEntityManager();

        $formModel->setDispatcher($dispatcher);
        $formModel->setTranslator($translator);
        $formModel->setEntityManager($entityManager);

        return $this->formModel = $formModel;
    }

    /**
     * Get submission model with real lead model.
     *
     * @return SubmissionModel
     */
    protected function getSubmissionModel()
    {
        $ipAddress = new IpAddress();

        $ipLookupHelper = $this
            ->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ipLookupHelper
            ->expects($this->any())
            ->method('getIpAddress')
            ->willReturn($ipAddress);

        $templatingHelperMock = $this->getTemplatingHelperMock();

        $formModel = $this->getFormModel();

        $pageModel = $this
            ->getMockBuilder(PageModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadModel = $this->getLeadModel();

        $campaignModel = $this
            ->getMockBuilder(CampaignModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadFieldModel = $this->getLeadFieldModel();

        $companyModel = $this
            ->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldHelperMock = $this->getFieldHelperMock();

        $uploadFieldValidatorMock = $this
            ->getMockBuilder(UploadFieldValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formUploaderMock = $this->getFormUploaderMock();

        $deviceTrackingService = $this->createMock(DeviceTrackingServiceInterface::class);

        $submissionModel = new SubmissionModel(
            $ipLookupHelper,
            $templatingHelperMock,
            $formModel,
            $pageModel,
            $leadModel,
            $campaignModel,
            $leadFieldModel,
            $companyModel,
            $fieldHelperMock,
            $uploadFieldValidatorMock,
            $formUploaderMock,
            $deviceTrackingService
        );

        $dispatcher    = $this->getDispatcher();
        $translator    = $this->getTranslator();
        $entityManager = $this->getEntityManager();

        $userHelper = $this
            ->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userHelper->expects($this
            ->any())
            ->method('getUser')
            ->willReturn(new User());

        $mockLogger = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $submissionModel->setDispatcher($dispatcher);
        $submissionModel->setTranslator($translator);
        $submissionModel->setEntityManager($entityManager);
        $submissionModel->setUserHelper($userHelper);
        $submissionModel->setLogger($mockLogger);

        return $submissionModel;
    }

    /**
     * Get lead model.
     *
     * @return LeadModel
     */
    protected function getLeadModel()
    {
        if ($this->leadModel) {
            return $this->leadModel;
        }

        $leadModel = $this
            ->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept([
                'createForm',
                'prepareParametersFromRequest',
                'cleanFields',
                'setFieldValues',
                'organizeFieldsByGroup',
            ])
            ->getMock();

        $leadModel->expects($this->any())
            ->method('getCurrentLead')
            ->will($this->returnCallback([$this, 'getCurrentLead']));

        $leadModel->expects($this->any())
            ->method('setCurrentLead')
            ->will($this->returnCallback([$this, 'setCurrentLead']));

        $leadFieldModel = $this->getLeadFieldModel();

        $integrationHelper = $this
            ->getMockBuilder(IntegrationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formFactory = $this->container->get('form.factory');

        $this->setProperty($leadModel, LeadModel::class, 'leadFieldModel', $leadFieldModel);
        $this->setProperty($leadModel, LeadModel::class, 'integrationHelper', $integrationHelper);
        $this->setProperty($leadModel, LeadModel::class, 'formFactory', $formFactory);

        return $this->leadModel = $leadModel;
    }

    /**
     * Get lead field model.
     *
     * @return LeadFieldModel
     */
    protected function getLeadFieldModel()
    {
        if ($this->leadFieldModel) {
            return $this->leadFieldModel;
        }

        $mockLeadField['email'] = [
            'id'                => 'email',
            'label'             => 'Email',
            'alias'             => 'email',
            'type'              => 'email',
            'group'             => 'core',
            'group_label'       => 'Core',
            'object'            => 'lead',
            'defaultValue'      => '',
            'properties'        => [],
            'isPublished'       => true,
            'isRequired'        => true,
            'isUniqueIdentifer' => true,
        ];

        $leadFieldModel = $this
            ->getMockBuilder(LeadFieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadFieldModel->expects($this->any())
            ->method('getEntities')
            ->willReturn($mockLeadField);

        $leadFieldModel->expects($this->any())
            ->method('getFieldListWithProperties')
            ->willReturn($mockLeadField);

        $leadFieldModel->expects($this->any())
            ->method('getUniqueIdentifierFields')
            ->willReturn($mockLeadField);

        return $this->leadFieldModel = $leadFieldModel;
    }

    /**
     * Get templating helper mock.
     *
     * @return TemplatingHelper
     */
    protected function getTemplatingHelperMock()
    {
        return $this
            ->getMockBuilder(TemplatingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get field helper mock.
     *
     * @return FieldHelper
     */
    protected function getFieldHelperMock()
    {
        return $this
            ->getMockBuilder(FormFieldHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get form uploader mock.
     *
     * @return FormUploader
     */
    protected function getFormUploaderMock()
    {
        return $this
            ->getMockBuilder(FormUploader::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get Event Dispatcher, lazily instantiated.
     *
     * @return EventDispatcher
     */
    protected function getDispatcher()
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventDispatcher();
        }

        return $this->dispatcher;
    }

    /**
     * Get translator, lazily instantiated.
     *
     * @return Translator
     */
    protected function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this
                ->getMockBuilder(Translator::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->translator;
    }

    /**
     * Get entity manager, lazily instantiated.
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $formRepository = $this->getMockBuilder(FormRepository::class)
                ->disableOriginalConstructor()
                ->getMock();

            $leadRepository = $this->getMockBuilder(LeadRepository::class)
                ->disableOriginalConstructor()
                ->getMock();

            $leadRepository->expects($this->any())
                ->method('getLeadsByUniqueFields')
                ->willReturn(null);

            $entityManager = $this
                ->getMockBuilder(EntityManager::class)
                ->disableOriginalConstructor()
                ->getMock();

            $entityManager->expects($this->any())
                ->method('getRepository')
                ->will(
                    $this->returnValueMap(
                        [
                            ['MauticLeadBundle:Lead', $leadRepository],
                            ['MauticFormBundle:Submission', $formRepository],
                            ['MauticFormBundle:Form', $formRepository],
                        ]
                    )
                );

            $this->entityManager = $entityManager;
        }

        return $this->entityManager;
    }

    public function getTestFormFields()
    {
        return [
            'email' => [
                'label'        => 'Email',
                'showLabel'    => 1,
                'saveResult'   => 1,
                'defaultValue' => false,
                'alias'        => 'email',
                'type'         => 'email',
                'leadField'    => 'email',
                'id'           => 'email',
            ],
        ];
    }

    /**
     * Get current lead.
     *
     * @param bool $tracking
     *
     * @return Lead
     */
    public function getCurrentLead($tracking = false)
    {
        if (!$this->currentLead) {
            $this->currentLead = new Lead();
        }

        return $tracking
            ? [$this->currentLead, $this->mockTrackingId, true]
            : $this->currentLead;
    }

    /**
     * Set current lead.
     *
     * @param Lead $Lead
     */
    public function setCurrentLead(Lead $lead)
    {
        $this->currentLead = $lead;
    }

    /**
     * Reset current lead.
     */
    private function resetCurrentLead()
    {
        $this->currentLead = new Lead();
    }

    /**
     * Set protected property to an object.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed  $value
     */
    private function setProperty($object, $class, $property, $value)
    {
        $reflectedProp = new \ReflectionProperty($class, $property);
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($object, $value);
    }
}
