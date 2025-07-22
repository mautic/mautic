<?php

namespace Mautic\FormBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\DTO\GlobalSearchFilterDTO;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Collector\MappedObjectCollectorInterface;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\Form\Type\FormType;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\ProgressiveProfiling\DisplayManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\FormFieldHelper as ContactFieldHelper;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;

/**
 * @extends CommonFormModel<Form>
 */
class FormModel extends CommonFormModel implements GlobalSearchInterface
{
    public function __construct(
        protected RequestStack $requestStack,
        protected Environment $twig,
        protected ThemeHelperInterface $themeHelper,
        protected ActionModel $formActionModel,
        protected FieldModel $formFieldModel,
        protected FormFieldHelper $fieldHelper,
        private PrimaryCompanyHelper $primaryCompanyHelper,
        protected LeadFieldModel $leadFieldModel,
        private FormUploader $formUploader,
        private ContactTracker $contactTracker,
        private ColumnSchemaHelper $columnSchemaHelper,
        private TableSchemaHelper $tableSchemaHelper,
        private MappedObjectCollectorInterface $mappedObjectCollector,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return FormRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Form::class);
    }

    public function getPermissionBase(): string
    {
        return 'form:forms';
    }

    public function getNameGetter(): string
    {
        return 'getName';
    }

    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Form) {
            throw new MethodNotAllowedHttpException(['Form']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(FormType::class, $entity, $options);
    }

    /**
     * @param string|int|null $id
     */
    public function getEntity($id = null): ?Form
    {
        if (null === $id) {
            return new Form();
        }

        $entity = parent::getEntity($id);

        if ($entity && $entity->getFields()) {
            foreach ($entity->getFields() as $field) {
                $this->addMappedFieldOptions($field);
            }
        }

        return $entity;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Form) {
            throw new MethodNotAllowedHttpException(['Form']);
        }

        switch ($action) {
            case 'pre_save':
                $name = FormEvents::FORM_PRE_SAVE;
                break;
            case 'post_save':
                $name = FormEvents::FORM_POST_SAVE;
                break;
            case 'pre_delete':
                $name = FormEvents::FORM_PRE_DELETE;
                break;
            case 'post_delete':
                $name = FormEvents::FORM_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new FormEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    public function setFields(Form $entity, $sessionFields): void
    {
        $order          = 1;
        $existingFields = $entity->getFields()->toArray();
        $formName       = $entity->generateFormName();
        foreach ($sessionFields as $key => $properties) {
            $isNew = (!empty($properties['id']) && isset($existingFields[$properties['id']])) ? false : true;
            $field = !$isNew ? $existingFields[$properties['id']] : new Field();

            if (!$isNew) {
                if (empty($properties['alias'])) {
                    $properties['alias'] = $field->getAlias();
                }
                if (empty($properties['label'])) {
                    $properties['label'] = $field->getLabel();
                }
            }

            if ($formName === $properties['alias']) {
                // Change the alias to prevent potential ID collisions in the rendered HTML
                $properties['alias'] = 'f_'.$properties['alias'];
            }

            foreach ($properties as $f => $v) {
                if (in_array($f, ['id', 'order'])) {
                    continue;
                }

                $func = 'set'.ucfirst($f);
                if (method_exists($field, $func)) {
                    $field->$func($v);
                }
            }
            $field->setForm($entity);
            $field->setSessionId($key);
            if (!$field->getParent()) {
                $field->setOrder($order);
                ++$order;
            } else {
                if (isset($sessionFields[$field->getParent()]['order'])) {
                    $field->setOrder($sessionFields[$field->getParent()]['order']);
                } else {
                    $field->setOrder($order);
                }
            }
            $entity->addField($properties['id'], $field);
        }

        // Persist if the entity is known
        if ($entity->getId()) {
            $this->formFieldModel->saveEntities($existingFields);
        }
    }

    public function deleteFields(Form $entity, $sessionFields): void
    {
        if (empty($sessionFields)) {
            return;
        }

        $existingFields = $entity->getFields()->toArray();
        $deleteFields   = [];
        foreach ($sessionFields as $fieldId) {
            if (!isset($existingFields[$fieldId])) {
                continue;
            }
            $this->handleFilesDelete($existingFields[$fieldId]);
            $entity->removeField($fieldId, $existingFields[$fieldId]);
            $deleteFields[] = $fieldId;
        }

        // Delete fields from db
        if (count($deleteFields)) {
            $this->formFieldModel->deleteEntities($deleteFields);
        }
    }

    private function handleFilesDelete(Field $field): void
    {
        if (!$field->isFileType()) {
            return;
        }

        $this->formUploader->deleteAllFilesOfFormField($field);
    }

    public function setActions(Form $entity, $sessionActions): void
    {
        $order           = 1;
        $existingActions = $entity->getActions()->toArray();
        $savedFields     = $entity->getFields()->toArray();

        // match sessionId with field Id to update mapped fields
        $fieldIds = [];
        foreach ($savedFields as $field) {
            $fieldIds[$field->getSessionId()] = $field->getId();
        }

        foreach ($sessionActions as $properties) {
            $isNew  = (!empty($properties['id']) && isset($existingActions[$properties['id']])) ? false : true;
            $action = !$isNew ? $existingActions[$properties['id']] : new Action();

            foreach ($properties as $f => $v) {
                if (in_array($f, ['id', 'order'])) {
                    continue;
                }

                $func = 'set'.ucfirst($f);

                if ('properties' == $f) {
                    if (isset($v['mappedFields'])) {
                        foreach ($v['mappedFields'] as $pk => $pv) {
                            if (str_contains($pv, 'new')) {
                                $v['mappedFields'][$pk] = $fieldIds[$pv];
                            }
                        }
                    }
                }

                if (method_exists($action, $func)) {
                    $action->$func($v);
                }
            }
            $action->setForm($entity);
            $action->setOrder($order);
            ++$order;
            $entity->addAction($properties['id'], $action);
        }

        // Persist if form is being edited
        if ($entity->getId()) {
            $this->formActionModel->saveEntities($existingActions);
        }
    }

    /**
     * @param array $actions
     */
    public function deleteActions(Form $entity, $actions): void
    {
        if (empty($actions)) {
            return;
        }

        $existingActions = $entity->getActions()->toArray();
        $deleteActions   = [];
        foreach ($actions as $actionId) {
            if (isset($existingActions[$actionId])) {
                $actionEntity = $this->em->getReference(Action::class, (int) $actionId);
                $entity->removeAction($actionEntity);
                $deleteActions[] = $actionId;
            }
        }

        // Delete actions from db
        if (count($deleteActions)) {
            $this->formActionModel->deleteEntities($deleteActions);
        }
    }

    public function saveEntity($entity, $unlock = true): void
    {
        $isNew = ($entity->getId()) ? false : true;

        if ($isNew && !$entity->getAlias()) {
            $alias = $this->cleanAlias($entity->getName(), '', 10);
            $entity->setAlias($alias);
        }

        $this->backfillReplacedPropertiesForBc($entity);

        // save the form so that the ID is available for the form html
        parent::saveEntity($entity, $unlock);

        // now build the form table
        if ($entity->getId()) {
            $this->createTableSchema($entity, $isNew);
        }

        $this->generateHtml($entity);
    }

    /**
     * Obtains the content.
     *
     * @param bool|true $withScript
     * @param bool|true $useCache
     */
    public function getContent(Form $form, $withScript = true, $useCache = true): string
    {
        $html = $this->getFormHtml($form, $useCache);

        if ($withScript) {
            $html = $this->getFormScript($form)."\n\n".$this->removeScriptTag($html);
        } else {
            $html = $this->removeScriptTag($html);
        }

        return $html;
    }

    /**
     * Obtains the cached HTML of a form and generates it if missing.
     *
     * @param bool|true $useCache
     *
     * @return string
     */
    public function getFormHtml(Form $form, $useCache = true)
    {
        if ($useCache && !$form->usesProgressiveProfiling()) {
            $cachedHtml = $form->getCachedHtml();
        }

        if (empty($cachedHtml)) {
            $cachedHtml = $this->generateHtml($form, $useCache);
        }

        if (!$form->getInKioskMode()) {
            $this->populateValuesWithLead($form, $cachedHtml);
        }

        return $cachedHtml;
    }

    /**
     * Get results for a form and lead.
     *
     * @param int $leadId
     * @param int $limit
     */
    public function getLeadSubmissions(Form $form, $leadId, $limit = 200): array
    {
        return $this->getRepository()->getFormResults(
            $form,
            [
                'leadId' => $leadId,
                'limit'  => $limit,
            ]
        );
    }

    /**
     * Generate the form's html.
     *
     * @param bool $persist
     */
    public function generateHtml(Form $entity, $persist = true): string
    {
        // Use specific template or system-wide default theme
        $theme         = $entity->getTemplate() ?? $this->coreParametersHelper->get('theme');
        $submissions   = null;
        $lead          = ($this->requestStack->getCurrentRequest()) ? $this->contactTracker->getContact() : null;
        $style         = '';
        $styleToRender = '@MauticForm/Builder/_style.html.twig';
        $formToRender  = '@MauticForm/Builder/form.html.twig';

        if ($this->twig->getLoader()->exists('@themes/'.$theme.'/html/MauticFormBundle/Builder/_style.html.twig')) {
            $styleToRender = '@themes/'.$theme.'/html/MauticFormBundle/Builder/_style.html.twig';
        }
        if ($this->twig->getLoader()->exists('@themes/'.$theme.'/html/MauticFormBundle/Builder/form.html.twig')) {
            $formToRender = '@themes/'.$theme.'/html/MauticFormBundle/Builder/form.html.twig';
        }

        if ($lead instanceof Lead && $lead->getId() && $entity->usesProgressiveProfiling()) {
            $submissions = $this->getLeadSubmissions($entity, $lead->getId());
        }

        if ($entity->getRenderStyle()) {
            $styleTheme = $styleToRender;
            $style      = $this->twig->render($this->themeHelper->checkForTwigTemplate($styleTheme));
        }

        // Determine pages
        $fields = $entity->getFields()->toArray();

        // Ensure the correct order in case this is generated right after a form save with new fields
        uasort($fields, fn ($a, $b): int => $a->getOrder() <=> $b->getOrder());

        $viewOnlyFields     = $this->getCustomComponents()['viewOnlyFields'];
        $displayManager     = new DisplayManager($entity, !empty($viewOnlyFields) ? $viewOnlyFields : []);
        [$pages, $lastPage] = $this->getPages($fields);
        $html               = $this->twig->render(
            $formToRender,
            [
                'fieldSettings'          => $this->getCustomComponents()['fields'],
                'viewOnlyFields'         => $viewOnlyFields,
                'fields'                 => $fields,
                'mappedFields'           => $this->mappedObjectCollector->buildCollection(...$entity->getMappedFieldObjects()),
                'form'                   => $entity,
                'theme'                  => '@themes/'.$entity->getTemplate().'/Field/',
                'submissions'            => $submissions,
                'lead'                   => $lead,
                'formPages'              => $pages,
                'lastFormPage'           => $lastPage,
                'style'                  => $style,
                'inBuilder'              => false,
                'displayManager'         => $displayManager,
                'successfulSubmitAction' => $this->coreParametersHelper->get('successful_submit_action'),
            ]
        );

        if (!$entity->usesProgressiveProfiling()) {
            $entity->setCachedHtml($html);

            if ($persist) {
                // bypass model function as events aren't needed for this
                $this->getRepository()->saveEntity($entity);
            }
        }

        return $html;
    }

    public function getPages(array $fields): array
    {
        $pages = ['open' => [], 'close' => []];

        $openFieldId  =
        $previousId   =
        $lastPage     = false;
        $pageCount    = 1;

        foreach ($fields as $fieldId => $field) {
            if ('pagebreak' == $field->getType() && $openFieldId) {
                // Open the page
                $pages['open'][$openFieldId] = $pageCount;
                $openFieldId                 = false;
                $lastPage                    = $fieldId;

                // Close the page at the next page break
                if ($previousId) {
                    $pages['close'][$previousId] = $pageCount;

                    ++$pageCount;
                }
            } else {
                if (!$openFieldId) {
                    $openFieldId = $fieldId;
                }
            }

            $previousId = $fieldId;
        }

        if ($openFieldId) {
            $pages['open'][$openFieldId] = $pageCount;
        }
        if ($previousId !== $lastPage) {
            $pages['close'][$previousId] = $pageCount;
        }

        return [$pages, $lastPage];
    }

    /**
     * Creates the table structure for form results.
     *
     * @param bool $isNew
     * @param bool $dropExisting
     */
    public function createTableSchema(Form $entity, $isNew = false, $dropExisting = false): void
    {
        // create the field as its own column in the leads table
        $name         = 'form_results_'.$entity->getId().'_'.$entity->getAlias();
        $columns      = $this->generateFieldColumns($entity);
        if ($isNew || (!$isNew && !$this->tableSchemaHelper->checkTableExists($name))) {
            $this->tableSchemaHelper->addTable([
                'name'    => $name,
                'columns' => $columns,
                'options' => [
                    'primaryKey'  => ['submission_id'],
                    'uniqueIndex' => ['submission_id', 'form_id'],
                ],
            ], true, $dropExisting);
            $this->tableSchemaHelper->executeChanges();
        } else {
            // check to make sure columns exist
            $columnSchemaHelper = $this->columnSchemaHelper->setName($name);
            foreach ($columns as $c) {
                if (!$columnSchemaHelper->checkColumnExists($c['name'])) {
                    $columnSchemaHelper->addColumn($c, false);
                }
            }
            $columnSchemaHelper->executeChanges();
        }
    }

    public function deleteEntity($entity): void
    {
        /* @var Form $entity */
        $this->deleteFormFiles($entity);

        if (!$entity->getId()) {
            // delete the associated results table
            $this->tableSchemaHelper->deleteTable('form_results_'.$entity->deletedId.'_'.$entity->getAlias());
            $this->tableSchemaHelper->executeChanges();
        }
        parent::deleteEntity($entity);
    }

    /**
     * @param mixed[] $ids
     *
     * @return mixed[]
     */
    public function deleteEntities($ids): array
    {
        $entities     = parent::deleteEntities($ids);
        foreach ($entities as $id => $entity) {
            /* @var Form $entity */
            // delete the associated results table
            $this->tableSchemaHelper->deleteTable('form_results_'.$id.'_'.$entity->getAlias());
            $this->deleteFormFiles($entity);
        }
        $this->tableSchemaHelper->executeChanges();

        return $entities;
    }

    private function deleteFormFiles(Form $form): void
    {
        $this->formUploader->deleteFilesOfForm($form);
    }

    /**
     * Generate an array of columns from fields.
     */
    public function generateFieldColumns(Form $form): array
    {
        $fields = $form->getFields()->toArray();

        $columns = [
            [
                'name' => 'submission_id',
                'type' => 'integer',
            ],
            [
                'name' => 'form_id',
                'type' => 'integer',
            ],
        ];
        $ignoreTypes = $this->getCustomComponents()['viewOnlyFields'];
        foreach ($fields as $f) {
            if (!in_array($f->getType(), $ignoreTypes)) {
                $columns[] = [
                    'name'    => $f->getAlias(),
                    'type'    => 'text',
                    'options' => [
                        'notnull' => false,
                    ],
                ];
            }
        }

        return $columns;
    }

    /**
     * Gets array of custom fields and submit actions from bundles subscribed FormEvents::FORM_ON_BUILD.
     *
     * @return mixed
     */
    public function getCustomComponents()
    {
        static $customComponents;

        if (empty($customComponents)) {
            // build them
            $event = new FormBuilderEvent($this->translator);
            $this->dispatcher->dispatch($event, FormEvents::FORM_ON_BUILD);
            $customComponents['fields']     = $event->getFormFields();
            $customComponents['actions']    = $event->getSubmitActions();
            $customComponents['choices']    = $event->getSubmitActionGroups();
            $customComponents['validators'] = $event->getValidators();

            // Generate a list of fields that are not persisted to the database by default
            $notPersist = ['button', 'captcha', 'freetext', 'freehtml', 'pagebreak'];
            foreach ($customComponents['fields'] as $type => $field) {
                if (isset($field['builderOptions']) && isset($field['builderOptions']['addSaveResult']) && false === $field['builderOptions']['addSaveResult']) {
                    $notPersist[] = $type;
                }
            }
            $customComponents['viewOnlyFields'] = $notPersist;
        }

        return $customComponents;
    }

    /**
     * Get the document write javascript for the form.
     */
    public function getAutomaticJavascript(Form $form): string
    {
        $html       = $this->getContent($form, false);
        $formScript = $this->getFormScript($form);

        // replace line breaks with literal symbol and escape quotations
        $search        = ["\r\n", "\n", '"'];
        $replace       = ['', '', '\"'];
        $html          = str_replace($search, $replace, $html);
        $oldFormScript = str_replace($search, $replace, $formScript);
        $newFormScript = $this->generateJsScript($formScript);

        // Write html for all browser and fallback for IE
        $script = '
            var scr  = document.currentScript;
            var html = "'.$html.'";

            if (scr !== undefined) {
                scr.insertAdjacentHTML("afterend", html);
                '.$newFormScript.'
            } else {
                document.write("'.$oldFormScript.'"+html);
            }
        ';

        return $script;
    }

    public function getFormScript(Form $form): string
    {
        $theme          = $form->getTemplate();
        $scriptToRender = '@MauticForm/Builder/_script.html.twig';

        if (!empty($theme)) {
            if ($this->twig->getLoader()->exists('@themes/'.$theme.'/MauticForm/Builder/_script.html.twig')) {
                $scriptToRender = '@themes/'.$theme.'/MauticForm/Builder/_script.html.twig';
            }
        }

        $script = $this->twig->render(
            $scriptToRender,
            [
                'form'  => $form,
                'theme' => $theme,
            ]
        );

        $html    = $this->getFormHtml($form);
        $scripts = $this->extractScriptTag($html);

        foreach ($scripts as $item) {
            $script .= $item."\n";
        }

        return $script;
    }

    /**
     * Writes in form values from get parameters.
     */
    public function populateValuesWithGetParameters(Form $form, &$formHtml): void
    {
        $formName = $form->generateFormName();
        $request  = $this->requestStack->getCurrentRequest();

        $fields = $form->getFields()->toArray();
        /** @var Field $f */
        foreach ($fields as $f) {
            $alias = $f->getAlias();
            if ($request->query->has($alias)) {
                $value = urlencode($request->query->get($alias));

                $this->fieldHelper->populateField($f, $value, $formName, $formHtml);
            }
        }
    }

    /**
     * @param string $formHtml
     */
    public function populateValuesWithLead(Form $form, &$formHtml): void
    {
        $formName          = $form->generateFormName();
        $fields            = $form->getFields();
        $autoFillFields    = [];
        $objectsToAutoFill = ['contact', 'company'];

        /** @var Field $field */
        foreach ($fields as $key => $field) {
            // we want work just with matched autofill fields
            if (
                $field->getMappedField()
                && $field->getIsAutoFill()
                && in_array($field->getMappedObject(), $objectsToAutoFill)
            ) {
                $autoFillFields[$key] = $field;
            }
        }

        // no fields for populate
        if (!count($autoFillFields)) {
            return;
        }

        $lead = $this->contactTracker->getContact();
        if (!$lead instanceof Lead) {
            return;
        }

        // get the contact (lead) and primary company field values
        $leadArray = $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead);
        if (!is_array($leadArray) || count($leadArray) <= 0) {
            return;
        }

        foreach ($autoFillFields as $field) {
            $value = $leadArray[$field->getMappedField()] ?? '';
            // just skip string empty field
            if ('' !== $value) {
                $this->fieldHelper->populateField($field, $value, $formName, $formHtml);
            }
        }
    }

    public function getFilterExpressionFunctions($operator = null): array
    {
        $operatorOptions = [
            '=' => [
                'label'       => 'mautic.lead.list.form.operator.equals',
                'expr'        => 'eq',
                'negate_expr' => 'neq',
            ],
            '!=' => [
                'label'       => 'mautic.lead.list.form.operator.notequals',
                'expr'        => 'neq',
                'negate_expr' => 'eq',
            ],
            'gt' => [
                'label'       => 'mautic.lead.list.form.operator.greaterthan',
                'expr'        => 'gt',
                'negate_expr' => 'lt',
            ],
            'gte' => [
                'label'       => 'mautic.lead.list.form.operator.greaterthanequals',
                'expr'        => 'gte',
                'negate_expr' => 'lt',
            ],
            'lt' => [
                'label'       => 'mautic.lead.list.form.operator.lessthan',
                'expr'        => 'lt',
                'negate_expr' => 'gt',
            ],
            'lte' => [
                'label'       => 'mautic.lead.list.form.operator.lessthanequals',
                'expr'        => 'lte',
                'negate_expr' => 'gt',
            ],
            'like' => [
                'label'       => 'mautic.lead.list.form.operator.islike',
                'expr'        => 'like',
                'negate_expr' => 'notLike',
            ],
            '!like' => [
                'label'       => 'mautic.lead.list.form.operator.isnotlike',
                'expr'        => 'notLike',
                'negate_expr' => 'like',
            ],
            'startsWith' => [
                'label'       => 'mautic.core.operator.starts.with',
                'expr'        => 'startsWith',
                'negate_expr' => 'startsWith',
            ],
            'endsWith' => [
                'label'       => 'mautic.core.operator.ends.with',
                'expr'        => 'endsWith',
                'negate_expr' => 'endsWith',
            ],
            'contains' => [
                'label'       => 'mautic.core.operator.contains',
                'expr'        => 'contains',
                'negate_expr' => 'contains',
            ],
        ];

        return (null === $operator) ? $operatorOptions : $operatorOptions[$operator];
    }

    /**
     * Get a list of assets in a date range.
     *
     * @param int   $limit
     * @param array $filters
     * @param array $options
     */
    public function getFormList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = []): array
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'forms', 't')
            ->setMaxResults($limit);

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Load HTML consider Libxml < 2.7.8.
     */
    private function loadHTML(&$dom, $html): void
    {
        if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
            $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } else {
            $dom->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8'));
        }
    }

    /**
     * Save HTML consider Libxml < 2.7.8.
     *
     * @return string
     */
    private function saveHTML($dom, $html)
    {
        if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
            return $dom->saveHTML($html);
        } else {
            // remove DOCTYPE, <html>, and <body> tags for old libxml
            return preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(['<html>', '</html>', '<body>', '</body>'], ['', '', '', ''], $dom->saveHTML($html)));
        }
    }

    /**
     * Extract script from html.
     */
    private function extractScriptTag($html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $this->loadHTML($dom, $html);
        $items = $dom->getElementsByTagName('script');

        $scripts = [];
        foreach ($items as $script) {
            $scripts[] = $this->saveHTML($dom, $script);
        }

        return $scripts;
    }

    /**
     * Remove script from html.
     */
    private function removeScriptTag($html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $this->loadHTML($dom, '<div>'.$html.'</div>');
        $items = $dom->getElementsByTagName('script');

        $remove = [];
        foreach ($items as $item) {
            $remove[] = $item;
        }

        foreach ($remove as $item) {
            $item->parentNode->removeChild($item);
        }

        $root   = $dom->documentElement;
        $result = '';
        foreach ($root->childNodes as $childNode) {
            $result .= $this->saveHTML($dom, $childNode);
        }

        return $result;
    }

    /**
     * Generate dom manipulation javascript to include all script.
     */
    private function generateJsScript($html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $this->loadHTML($dom, '<div>'.$html.'</div>');
        $items = $dom->getElementsByTagName('script');

        $javascript = '';
        foreach ($items as $key => $script) {
            if ($script->hasAttribute('src')) {
                $javascript .= "
                var script$key = document.createElement('script');
                script$key.src = '".$script->getAttribute('src')."';
                document.getElementsByTagName('head')[0].appendChild(script$key);";
            } else {
                $scriptContent = $script->nodeValue;
                $scriptContent = str_replace(["\r\n", "\n", '"'], ['', '', '\"'], $scriptContent);

                $javascript .= "
                var inlineScript$key = document.createTextNode(\"$scriptContent\");
                var script$key       = document.createElement('script');
                script$key.appendChild(inlineScript$key);
                document.getElementsByTagName('head')[0].appendChild(script$key);";
            }
        }

        return $javascript;
    }

    /**
     * Finds out whether the.
     */
    private function addMappedFieldOptions(Field $formField): void
    {
        $formFieldProps   = $formField->getProperties();
        $mappedFieldAlias = $formField->getMappedField();

        if (empty($formFieldProps['syncList']) || empty($mappedFieldAlias) || 'contact' !== $formField->getMappedObject()) {
            return;
        }

        $list = $this->getContactFieldPropertiesList($mappedFieldAlias);

        if (!empty($list)) {
            $formFieldProps['list'] = ['list' => $list];
            if (array_key_exists('optionlist', $formFieldProps)) {
                $formFieldProps['optionlist'] = ['list' => $list];
            }
            $formField->setProperties($formFieldProps);
        }
    }

    /**
     * @return mixed[]|null
     */
    public function getContactFieldPropertiesList(string $contactFieldAlias): ?array
    {
        $contactField = $this->leadFieldModel->getEntityByAlias($contactFieldAlias); // @todo this must use all objects as well. Not just contact.

        if (empty($contactField) || !in_array($contactField->getType(), ContactFieldHelper::getListTypes())) {
            return null;
        }

        $contactFieldProps = $contactField->getProperties();

        switch ($contactField->getType()) {
            case 'select':
            case 'multiselect':
                $list = $contactFieldProps['list'] ?? [];
                break;
            case 'lookup':
                $list = $contactFieldProps['list'] ?? [];
                $list = array_combine(array_column($list, 'value'), array_column($list, 'label'));
                break;
            case 'boolean':
                $list = [$contactFieldProps['no'], $contactFieldProps['yes']];
                break;
            case 'country':
                $list = ContactFieldHelper::getCountryChoices();
                break;
            case 'region':
                $list = ContactFieldHelper::getRegionChoices();
                break;
            case 'timezone':
                $list = ContactFieldHelper::getTimezonesChoices();
                break;
            case 'locale':
                $list = ContactFieldHelper::getLocaleChoices();
                break;
            default:
                return null;
        }

        return $list;
    }

    /**
     * @param string $fieldAlias
     *
     * @return Field|null
     */
    public function findFormFieldByAlias(Form $form, $fieldAlias)
    {
        foreach ($form->getFields() as $field) {
            if ($field->getAlias() === $fieldAlias) {
                return $field;
            }
        }

        return null;
    }

    private function backfillReplacedPropertiesForBc(Form $entity): void
    {
        /** @var Field $field */
        foreach ($entity->getFields() as $field) {
            if (!$field->getLeadField() && $field->getMappedField()) {
                $field->setLeadField($field->getMappedField());
            } elseif ($field->getLeadField() && !$field->getMappedField()) {
                $field->setMappedField($field->getLeadField());
                $field->setMappedObject(
                    str_starts_with($field->getLeadField(), 'company') && 'company' !== $field->getLeadField() ? 'company' : 'contact'
                );
            }
        }
    }

    public function getEntitiesForGlobalSearch(GlobalSearchFilterDTO $filterDTO): ?Paginator
    {
        $filter = $filterDTO->getFilters();

        if (!$this->canViewOthersEntity()) {
            $filter['force'][] = [
                'column' => $this->getRepository()->getTableAlias().'.createdBy',
                'expr'   => 'eq',
                'value'  => $this->userHelper->getUser()->getId(),
            ];
        }

        return $this->getRepository()->getEntitiesForGlobalSearch($filter);
    }
}
