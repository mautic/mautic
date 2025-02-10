<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FormImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormModel $formModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_FORM          => ['onFormExport', 0],
            EntityImportEvent::IMPORT_CAMPAIGN_FORM => ['onFormImport', 0],
        ];
    }

    public function onFormExport(EntityExportEvent $event): void
    {
        $campaignId = $event->getEntityId();
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $formActions = $queryBuilder
            ->select('action.name, action.description, action.type, action.action_order, action.properties')
            ->from('form_actions', 'action')
            ->where('action.form_id = :formId')
            ->setParameter('formId', $formId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($formResults as $result) {
            $data = [
                'id'           => $result['form_id'],
                'name'         => $result['name'],
                'is_published' => $result['is_published'],
                'category_id'  => $result['category_id'],
                'description'  => $result['description'],
                'alias'        => $result['alias'],
                'lang'         => $result['lang'],
                'cached_html'  => $result['cached_html'],
                'post_action'  => $result['post_action'],
                'template'     => $result['template'],
                'form_type'    => $result['form_type'],
                'render_style' => $result['render_style'],
                'post_action_property' => $result['post_action_property'],
                'form_attr'    => $result['form_attr'],
            ];

            $dependency = [
                'campaignId' => (int) $campaignId,
                'segmentId'  => (int) $result['form_id'],
            ];

            $event->addDependencyEntity(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $dependency);
            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $data);
        }
    }

    public function onFormImport(EntityImportEvent $event): void
    {
        $output = new ConsoleOutput();
        $forms = $event->getEntityData();

        if (!$forms) {
            return;
        }

        foreach ($forms as $formData) {
            $form = new \Mautic\FormBundle\Entity\Form();
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

            $this->entityManager->persist($form);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $formData['id'], (int) $form->getId());

            $output->writeln('<info>Imported form: '.$form->getName().' with ID: '.$form->getId().'</info>');
        }
    }
}
