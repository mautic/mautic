<?php

namespace Mautic\FormBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\FormFieldEvent;
use Mautic\FormBundle\Form\Type\FieldType;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends CommonFormModel<Field>
 */
class FieldModel extends CommonFormModel
{
    public function __construct(
        protected LeadFieldModel $leadFieldModel,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
        private RequestStack $requestStack,
        private ColumnSchemaHelper $columnSchemaHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * @param object|array<mixed> $entity
     * @param string|null         $action
     * @param array               $options
     *
     * @return \Symfony\Component\Form\FormInterface<mixed>
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if ($action) {
            $options['action'] = $action;
        }

        return $formFactory->create(FieldType::class, $entity, $options);
    }

    /**
     * @return \Mautic\FormBundle\Entity\FieldRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Field::class);
    }

    public function getPermissionBase(): string
    {
        return 'form:forms';
    }

    public function getEntity($id = null): ?Field
    {
        if (null === $id) {
            return new Field();
        }

        return parent::getEntity($id);
    }

    /**
     * Get the fields saved in session.
     */
    public function getSessionFields($formId): array
    {
        $fields = $this->getSession()->get('mautic.form.'.$formId.'.fields.modified', []);
        $remove = $this->getSession()->get('mautic.form.'.$formId.'.fields.deleted', []);

        return array_diff_key($fields, array_flip($remove));
    }

    /**
     * @param string[] $aliases
     */
    public function generateAlias(string $label, array &$aliases): string
    {
        $alias = $this->cleanAlias($label, 'f_', 25);

        // make sure alias is not already taken
        $testAlias = $alias;

        $count    = (int) in_array($alias, $aliases);
        $aliasTag = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $count     = (int) in_array($testAlias, $aliases);
            ++$aliasTag;
        }

        // Prevent internally used identifiers in the form HTML from colliding with the generated field's ID
        $internalUse = ['message', 'error', 'id', 'return', 'name', 'messenger'];
        if (in_array($testAlias, $internalUse)) {
            $testAlias = 'f_'.$testAlias;
        }

        $aliases[] = $testAlias;

        return $testAlias;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Field) {
            throw new MethodNotAllowedHttpException(['Form']);
        }

        switch ($action) {
            case 'pre_save':
                $name = FormEvents::FIELD_PRE_SAVE;
                break;
            case 'post_save':
                $name = FormEvents::FIELD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = FormEvents::FIELD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = FormEvents::FIELD_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new FormFieldEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * Updates the table structure for form results.
     */
    public function removeFieldColumn(Field $field): void
    {
        $form = $field->getForm();

        $name = 'form_results_'.$form->getId().'_'.$form->getAlias();

        $schemaHelper = $this->columnSchemaHelper->setName($name);
        $schemaHelper->dropColumn($field->getAlias());
        $schemaHelper->executeChanges();
    }
}
