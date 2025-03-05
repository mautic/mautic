<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FormImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormModel $formModel,
        private UserModel $userModel,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class  => ['onFormExport', 0],
            EntityImportEvent::class  => ['onFormImport', 0],
        ];
    }

    public function onFormExport(EntityExportEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $formId       = $event->getEntityId();
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $form = $this->formModel->getEntity($formId);
        if (!$form) {
            return;
        }

        $formActions = $queryBuilder
            ->select('action.name, action.description, action.type, action.action_order, action.properties')
            ->from('form_actions', 'action')
            ->where('action.form_id = :formId')
            ->setParameter('formId', $formId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $formFields = $queryBuilder
            ->select('field.label, field.alias, field.type, field.is_required, field.properties')
            ->from('form_fields', 'field')
            ->where('field.form_id = :formId')
            ->setParameter('formId', $formId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        $data = [
            'id'                   => $formId,
            'name'                 => $form->getName(),
            'is_published'         => $form->isPublished(),
            'description'          => $form->getDescription(),
            'alias'                => $form->getAlias(),
            'lang'                 => $form->getLanguage(),
            'cached_html'          => $form->getCachedHtml(),
            'post_action'          => $form->getPostAction(),
            'template'             => $form->getTemplate(),
            'form_type'            => $form->getFormType(),
            'render_style'         => $form->getRenderStyle(),
            'post_action_property' => $form->getPostActionProperty(),
            'form_attr'            => $form->getFormAttributes(),
            'form_actions'         => $formActions,
            'form_fields'          => $formFields,
        ];

        $event->addEntity(Form::ENTITY_NAME, $data);
    }

    public function onFormImport(EntityImportEvent $event): void
    {
        if (Form::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $output   = new ConsoleOutput();
        $forms    = $event->getEntityData();
        $userId   = $event->getUserId();
        $userName = '';

        if ($userId) {
            $user   = $this->userModel->getEntity($userId);
            if ($user) {
                $userName = $user->getFirstName().' '.$user->getLastName();
            } else {
                $output->writeln('User ID '.$userId.' not found. Campaigns will not have a created_by_user field set.');
            }
        }

        if (!$forms) {
            return;
        }

        foreach ($forms as $formData) {
            $form = new Form();
            $form->setName($formData['name']);
            $form->setIsPublished((bool) $formData['is_published']);
            $form->setDescription($formData['description'] ?? '');
            $form->setAlias($formData['alias'] ?? '');
            $form->setLanguage($formData['lang'] ?? null);
            $form->setCachedHtml($formData['cached_html'] ?? '');
            $form->setPostAction($formData['post_action'] ?? '');
            $form->setPostActionProperty($formData['post_action_property'] ?? '');
            $form->setTemplate($formData['template'] ?? '');
            $form->setFormType($formData['form_type'] ?? '');
            $form->setRenderStyle($formData['render_style'] ?? '');
            $form->setFormAttributes($formData['form_attr'] ?? '');
            $form->setDateAdded(new \DateTime());
            $form->setDateModified(new \DateTime());
            $form->setCreatedByUser($userName);

            $this->entityManager->persist($form);
            $this->entityManager->flush();

            // Import form actions
            if (!empty($formData['form_actions'])) {
                foreach ($formData['form_actions'] as $actionData) {
                    $action = new \Mautic\FormBundle\Entity\Action();
                    $action->setForm($form);
                    $action->setName($actionData['name']);
                    $action->setDescription($actionData['description'] ?? '');
                    $action->setType($actionData['type']);
                    $action->setOrder($actionData['action_order'] ?? 0);
                    $action->setProperties($actionData['properties'] ?? []);

                    $this->entityManager->persist($action);
                }
            }

            // Import form fields
            if (!empty($formData['form_fields'])) {
                foreach ($formData['form_fields'] as $fieldData) {
                    $field = new \Mautic\FormBundle\Entity\Field();
                    $field->setForm($form);
                    $field->setLabel($fieldData['label']);
                    $field->setAlias($fieldData['alias']);
                    $field->setType($fieldData['type']);
                    $field->setIsRequired((bool) $fieldData['is_required']);
                    $field->setProperties($fieldData['properties'] ?? []);

                    $this->entityManager->persist($field);
                }
            }

            $this->entityManager->flush();

            $event->addEntityIdMap((int) $formData['id'], (int) $form->getId());
            $output->writeln('<info>Imported form: '.$form->getName().' with ID: '.$form->getId().'</info>');
        }
    }
}
