<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSendExampleApiBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Controller\EmailController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\FakeContactHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Email>
 */
class ExampleSendApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var EmailModel|null
     */
    protected $model;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $emailModel = $modelFactory->getModel('email');
        \assert($emailModel instanceof EmailModel);

        $this->model         = $emailModel;
        $this->entityClass   = Email::class;
        $this->entityNameOne = 'email';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Sends an example of the email to the given addresses without creating
     * stats, mirroring the UI "Send example" action
     * (EmailController::sendExampleAction).
     *
     * Request body (JSON or form-encoded):
     *  - recipients      array of email addresses (required)
     *  - contactId       int, fill tokens from this contact instead of fake data (optional)
     *  - tokens          object of token => value overrides (optional)
     *  - noSubjectPrefix bool, skip the "[TEST]" subject prefix (optional)
     *
     * @param int $id Email ID
     */
    public function sendExampleAction(Request $request, $id, LeadModel $leadModel, FakeContactHelper $fakeContactHelper): Response
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        $post       = $request->request->all();
        $recipients = array_filter(array_map('trim', (array) ($post['recipients'] ?? [])));

        if (empty($recipients)) {
            return $this->badRequest('recipients is required and must be a non-empty array of email addresses');
        }

        $cleanTokens = [];
        foreach ((array) ($post['tokens'] ?? []) as $token => $value) {
            $value = InputHelper::html($value);
            if (!preg_match('/^{.*?}$/', $token)) {
                $token = '{'.$token.'}';
            }

            $cleanTokens[$token] = $value;
        }

        $fields = null;
        if (!empty($post['contactId'])) {
            $lead = $this->checkLeadAccess((int) $post['contactId'], 'view');
            if ($lead instanceof Response) {
                return $lead;
            }
            \assert($lead instanceof Lead);

            $fields = $leadModel->getRepository()->getLead($lead->getId());
            $fields = $this->model->enrichedContactWithCompanies($fields);
        }

        if (null === $fields) {
            $fields = $fakeContactHelper->prepareFakeContactWithPrimaryCompany();
        }

        // Prefix the subject with [TEST] like the UI action does, but capture the original
        // first so it can be restored afterwards — an example send must never mutate (and
        // risk persisting a change to) the stored email.
        $originalSubject = $entity->getSubject();
        if (empty($post['noSubjectPrefix'])) {
            $entity->setSubject(sprintf('%s %s', EmailController::EXAMPLE_EMAIL_SUBJECT_PREFIX, $originalSubject));
        }

        $sent   = [];
        $errors = [];
        foreach ($recipients as $recipient) {
            $users = [
                [
                    // Unknown user, same shape the UI action uses
                    'id'        => '',
                    'firstname' => '',
                    'lastname'  => '',
                    'email'     => $recipient,
                ],
            ];

            $error = $this->model->sendSampleEmailToUser($entity, $users, $fields, $cleanTokens, [], false);
            if (is_array($error) && count($error)) {
                $errors[] = $error[0];
            } else {
                $sent[] = $recipient;
            }
        }

        // Restore the original subject so the prefix is never persisted to the entity.
        $entity->setSubject($originalSubject);

        $view = $this->view(
            [
                'success' => 0 === count($errors),
                'sent'    => $sent,
                'errors'  => $errors,
            ],
            Response::HTTP_OK
        );

        return $this->handleView($view);
    }
}
