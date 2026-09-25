<?php

namespace Mautic\EmailBundle\Controller\Api;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\RandomHelper\RandomHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\FakeContactHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Email>
 */
final class EmailApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var EmailModel|null
     */
    protected $model;

    /**
     * @var array<string, mixed>
     */
    protected $extraGetEntitiesArguments = ['ignoreListJoin' => true];

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        EmailModel $emailModel,
    ) {
        $this->model            = $emailModel;
        $this->entityClass      = Email::class;
        $this->entityNameOne    = 'email';
        $this->entityNameMulti  = 'emails';
        $this->serializerGroups = [
            'emailDetails',
            'categoryList',
            'publishDetails',
            'assetList',
            'formList',
            'leadListList',
        ];
        $this->dataInputMasks   = [
            'customHtml'     => 'html',
            'dynamicContent' => [
                'content' => 'html',
                'filters' => [
                    'content' => 'html',
                ],
            ],
        ];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Obtains a list of emails.
     */
    public function getEntitiesAction(Request $request, UserHelper $userHelper): Response
    {
        // get parent level only
        $this->listFilters[] = [
            'column' => 'e.variantParent',
            'expr'   => 'isNull',
        ];

        return parent::getEntitiesAction($request, $userHelper);
    }

    /**
     * Sends the email to it's assigned lists.
     *
     * @param int $id Email ID
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendAction(Request $request, $id): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity || !$entity->isPublished()) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        $lists = $request->request->all()['lists'] ?? [];
        $limit = $request->request->get('limit');
        $batch = $request->request->get('batch');

        [$count, $failed] = $this->model->sendEmailToLists($entity, $lists, $limit, $batch);

        $view = $this->view(
            [
                'success'          => 1,
                'sentCount'        => $count,
                'failedRecipients' => $failed,
            ],
            Response::HTTP_OK
        );

        return $this->handleView($view);
    }

    /**
     * Sends the email to a specific lead.
     *
     * @param int $id     Email ID
     * @param int $leadId Lead ID
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendLeadAction(Request $request, $id, $leadId): Response
    {
        $entity = $this->model->getEntity($id);

        if (!$entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        /** @var Lead $lead */
        $lead = $this->checkLeadAccess($leadId, 'edit');
        if ($lead instanceof Response) {
            return $lead;
        }

        $post        = $request->request->all();
        $assetsIds   = (!empty($post['assetAttachments'])) ? $post['assetAttachments'] : [];
        $response    = ['success' => false];
        $cleanTokens = $this->cleanTokens((!empty($post['tokens'])) ? $post['tokens'] : []);

        $leadFields = array_merge(['id' => $leadId], $lead->getProfileFields());
        // Set owner_id to support the "Owner is mailer" feature
        if ($lead->getOwner()) {
            $leadFields['owner_id'] = $lead->getOwner()->getId();
        }

        $result = $this->model->sendEmail(
            $entity,
            $leadFields,
            [
                'source'            => ['api', 0],
                'tokens'            => $cleanTokens,
                'assetAttachments'  => $assetsIds,
                'return_errors'     => true,
                'ignoreDNC'         => $entity->getSendToDnc(),
            ]
        );

        if (is_bool($result)) {
            $response['success'] = $result;
        } else {
            $response['failed'] = $result;
        }

        $view = $this->view($response, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * @param string $trackingHash
     */
    public function replyAction(Reply $replyService, RandomHelperInterface $randomHelper, $trackingHash): Response
    {
        try {
            $replyService->createReplyByHash($trackingHash, "api-{$randomHelper->generate()}");
        } catch (EntityNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        return $this->handleView(
            $this->view(['success' => true], Response::HTTP_CREATED)
        );
    }

    /**
     * Sends an example (proof) copy of the email to arbitrary addresses, mirroring the UI
     * "Send example" action (EmailController::sendExampleAction). No stats are recorded and
     * the stored email is not modified; works on unpublished emails.
     *
     * Request body:
     *  - recipients      array of email addresses (required)
     *  - contactId       int, fill tokens from this contact instead of fake data (optional)
     *  - tokens          object of token => value overrides (optional)
     *  - noSubjectPrefix bool, skip the "[TEST]" subject prefix (optional)
     *
     * @param int $id Email ID
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendExampleAction(Request $request, $id, LeadModel $leadModel, FakeContactHelper $fakeContactHelper): Response
    {
        $entity     = $this->model->getEntity($id);
        $post       = $request->request->all();
        $recipients = array_filter(array_map('trim', (array) ($post['recipients'] ?? [])));

        $validation = $this->validateExampleRequest($entity, $recipients);
        if ($validation instanceof Response) {
            return $validation;
        }
        \assert($entity instanceof Email);

        $fields = $this->resolveExampleContactFields($post, $leadModel, $fakeContactHelper);
        if ($fields instanceof Response) {
            return $fields;
        }

        $result = $this->sendExampleToRecipients(
            $entity,
            $recipients,
            $fields,
            $this->cleanTokens((array) ($post['tokens'] ?? [])),
            empty($post['noSubjectPrefix'])
        );

        return $this->handleView($this->view($result, Response::HTTP_OK));
    }

    /**
     * @param string[] $recipients
     */
    private function validateExampleRequest(?Email $entity, array $recipients): ?Response
    {
        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        return empty($recipients)
            ? $this->badRequest('recipients is required and must be a non-empty array of email addresses')
            : null;
    }

    /**
     * Returns the contact field data used to fill tokens: a real contact's data when a
     * valid contactId is supplied, otherwise fake placeholder data. Returns a Response if
     * access to the requested contact is denied.
     *
     * @param array<string, mixed> $post
     *
     * @return array<int|string, mixed>|Response
     */
    private function resolveExampleContactFields(array $post, LeadModel $leadModel, FakeContactHelper $fakeContactHelper): Response|array
    {
        if (empty($post['contactId'])) {
            return $fakeContactHelper->prepareFakeContactWithPrimaryCompany();
        }

        $lead = $this->checkLeadAccess((int) $post['contactId'], 'view');
        if ($lead instanceof Response) {
            return $lead;
        }
        \assert($lead instanceof Lead);

        return $this->model->enrichedContactWithCompanies($leadModel->getRepository()->getLead($lead->getId()));
    }

    /**
     * @param string[]                 $recipients
     * @param array<int|string, mixed> $fields
     * @param array<string, mixed>     $tokens
     *
     * @return array{success: bool, sent: string[], errors: string[]}
     */
    private function sendExampleToRecipients(Email $entity, array $recipients, array $fields, array $tokens, bool $applyPrefix): array
    {
        // Prefix the subject with [TEST] like the UI action, capturing the original first so
        // it can be restored afterwards — an example send must never mutate (and risk
        // persisting a change to) the stored email.
        $originalSubject = $entity->getSubject();
        if ($applyPrefix) {
            $entity->setSubject(sprintf('%s %s', EmailController::EXAMPLE_EMAIL_SUBJECT_PREFIX, $originalSubject));
        }

        $sent   = [];
        $errors = [];
        foreach ($recipients as $recipient) {
            $users = [
                [
                    // Setting id, firstname and lastname to empty as this is an unknown user
                    'id'        => '',
                    'firstname' => '',
                    'lastname'  => '',
                    'email'     => $recipient,
                ],
            ];

            $error = $this->model->sendSampleEmailToUser($entity, $users, $fields, $tokens, [], false);
            if (is_array($error) && !empty($error)) {
                $errors[] = $error[0];
            } else {
                $sent[] = $recipient;
            }
        }

        // Restore the original subject so the prefix is never persisted to the entity.
        $entity->setSubject($originalSubject);

        return [
            'success' => empty($errors),
            'sent'    => $sent,
            'errors'  => $errors,
        ];
    }

    /**
     * Normalises a token map: HTML-cleans values and wraps bare token names in braces.
     *
     * @param array<int|string, mixed> $tokens
     *
     * @return array<string, mixed>
     */
    private function cleanTokens(array $tokens): array
    {
        $cleanTokens = [];
        foreach ($tokens as $token => $value) {
            $value = InputHelper::html($value);
            if (!preg_match('/^{.*?}$/', (string) $token)) {
                $token = '{'.$token.'}';
            }

            $cleanTokens[$token] = $value;
        }

        return $cleanTokens;
    }

    protected function prepareParametersFromRequest(FormInterface $form, array &$params, ?object $entity = null, array $masks = [], array $fields = []): void
    {
        if (isset($params['publicPreview']) && $entity instanceof Email) {
            $entity->setPublicPreview(InputHelper::boolean($params['publicPreview']) ?? false);
            unset($params['publicPreview']);
        }
        parent::prepareParametersFromRequest($form, $params, $entity, $masks, $fields);
    }

    /**
     * Processes API Form.
     *
     * @param Email        $entity
     * @param mixed[]|null $parameters
     * @param string       $method
     *
     * @return mixed
     */
    protected function processForm(Request $request, $entity, $parameters = null, $method = 'PUT')
    {
        if (array_key_exists('sendToDnc', $parameters)
            && !$this->security->isGranted('email:emails:sendtodnc')) {
            // do not save user defined value for sendToDnc if user do not have permission
            unset($parameters['sendToDnc']);
        }

        if (Request::METHOD_PUT === $method && !array_key_exists('sendToDnc', $parameters)) {
            // use default value, in case of PUT method it does not use default value if entity is already exist and tried to call setter method with null value.
            $parameters['sendToDnc'] = false;
        }

        return parent::processForm($request, $entity, $parameters, $method);
    }
}
