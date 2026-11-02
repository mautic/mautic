<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Controller;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticCompanyEnrichBundle\Api\Client;
use MauticPlugin\MauticCompanyEnrichBundle\Form\Type\ResearchType;
use MauticPlugin\MauticCompanyEnrichBundle\Helper\ProfileMapper;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CompanyEnrichController extends CommonController
{
    public function researchAction(Request $request, Client $client, IntegrationsHelper $integrationsHelper): Response
    {
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            $this->throwAccessDenied();
        }
        $form = $this->createForm(ResearchType::class, ['operation' => 'enrich_company', 'query' => '{"domain":"example.com"}', 'body' => '{}'], [
            'action' => $this->generateUrl('mautic_companyenrich_research'),
        ]);
        $form->handleRequest($request);
        $result = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $input = $form->getData();
                $query = $this->decode($input['query'] ?? '{}');
                $body = $this->decode($input['body'] ?? '{}');
                $result = $client->query($this->apiKey($integrationsHelper), $input['operation'], $query, $body);
            } catch (\InvalidArgumentException|\RuntimeException $error) {
                $form->addError(new FormError($error->getMessage()));
            }
        }

        return $this->delegateView([
            'viewParameters' => ['form' => $form->createView(), 'result' => $result, 'operation' => $form->get('operation')->getData(), 'operations' => Client::OPERATIONS],
            'contentTemplate' => '@MauticCompanyEnrich/Research/index.html.twig',
            'passthroughVars' => ['activeLink' => '#mautic_companyenrich_research', 'mauticContent' => 'companyenrich', 'route' => $this->generateUrl('mautic_companyenrich_research')],
        ]);
    }

    public function enrichAction(Request $request, string $kind, int $id, LeadModel $leadModel, CompanyModel $companyModel, EntityManagerInterface $em, Client $client, IntegrationsHelper $integrationsHelper): Response
    {
        if (!$this->security->isGranted('plugin:plugins:manage') || !in_array($kind, ['contact', 'company'], true)) {
            $this->throwAccessDenied();
        }
        $model = 'contact' === $kind ? $leadModel : $companyModel;
        $entity = $model->getEntity($id);
        if ((!$entity instanceof Lead && !$entity instanceof Company) || $entity->getId() !== $id) {
            throw $this->createNotFoundException();
        }
        $this->checkEditAccess($entity);
        $identifier = 'contact' === $kind ? $entity->getEmail() : $entity->getFieldValue('companywebsite');
        $form = $this->createFormBuilder([], [
            'csrf_token_id' => 'companyenrich_'.$kind.'_'.$id,
            'action' => $this->generateUrl('mautic_companyenrich_enrich', ['kind' => $kind, 'id' => $id]),
        ])
            ->add('run', SubmitType::class, ['label' => 'mautic.companyenrich.fill', 'attr' => ['class' => 'btn btn-primary']])->getForm();
        $form->handleRequest($request);
        $updated = null;
        $creditsConsumed = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!is_string($identifier) || '' === trim($identifier)) {
                    throw new \InvalidArgumentException('The record needs an existing email address or company website.');
                }
                $stamp = $entity->getDateModified()?->format('c');
                $result = 'contact' === $kind
                    ? $client->query($this->apiKey($integrationsHelper), 'lookup_person', [], ['email' => $identifier])
                    : $client->query($this->apiKey($integrationsHelper), 'enrich_company', ['domain' => $identifier]);
                $creditsConsumed = $result['credits_consumed'];
                $updated = $em->wrapInTransaction(function () use ($em, $entity, $model, $kind, $id, $identifier, $stamp, $result): ?array {
                    // Lock only after the remote call, then reload both fixed and custom fields.
                    $em->refresh($entity, LockMode::PESSIMISTIC_WRITE);
                    $entity->setFields($model->getRepository()->getFieldValues($id, true, 'contact' === $kind ? 'lead' : 'company'));
                    $this->checkEditAccess($entity);
                    $currentIdentifier = 'contact' === $kind ? $entity->getEmail() : $entity->getFieldValue('companywebsite');
                    if ($currentIdentifier !== $identifier || $entity->getDateModified()?->format('c') !== $stamp) {
                        return null;
                    }
                    $changed = [];
                    foreach (ProfileMapper::fields($kind, (array) $result['data']) as $field => $value) {
                        if ($entity->getField($field) && in_array($entity->getFieldValue($field), [null, ''], true)) {
                            $entity->addUpdatedField($field, $value);
                            $changed[] = $field;
                        }
                    }
                    if ($changed) {
                        $model->saveEntity($entity);
                    }

                    return $changed;
                });
                if (null === $updated) {
                    throw new \RuntimeException('The record changed during research. No fields were updated. The lookup may have consumed credits.');
                }
            } catch (\InvalidArgumentException|\RuntimeException $error) {
                $form->addError(new FormError($error->getMessage()));
            }
        }

        return $this->delegateView([
            'viewParameters' => ['form' => $form->createView(), 'identifier' => $identifier, 'updated' => $updated, 'creditsConsumed' => $creditsConsumed],
            'contentTemplate' => '@MauticCompanyEnrich/Research/enrich.html.twig',
            'passthroughVars' => ['mauticContent' => 'companyenrich'],
        ]);
    }

    private function checkEditAccess(Lead|Company $entity): void
    {
        // Mautic company records share the contact entity permissions.
        if (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())
            || !$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $entity->getPermissionUser())) {
            $this->throwAccessDenied();
        }
    }

    private function apiKey(IntegrationsHelper $helper): string
    {
        try {
            $configuration = $helper->getIntegration('CompanyEnrich')->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException) {
            throw new \RuntimeException('Install and enable CompanyEnrich in Plugins before running research.');
        }
        if (!$configuration->getIsPublished()) {
            throw new \RuntimeException('Enable CompanyEnrich in Plugins before running research.');
        }

        return (string) ($configuration->getApiKeys()['apikey'] ?? '');
    }

    /** @return array<string, mixed> */
    private function decode(?string $value): array
    {
        try {
            $decoded = json_decode(trim($value ?? '') ?: '{}', true, 24, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException) {
            throw new \InvalidArgumentException('Enter a valid JSON object for query parameters and filters.');
        }
        if (!is_array($decoded) || ([] !== $decoded && array_is_list($decoded))) {
            throw new \InvalidArgumentException('Query parameters and filters must be JSON objects.');
        }

        return $decoded;
    }
}
