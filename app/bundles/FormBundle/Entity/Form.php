<?php

namespace Mautic\FormBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "form:read"
 *        },
 *       "swagger_definition_name"="Read",
 *       "api_included"={"category", "fields", "actions"}
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "form:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Form extends FormEntity implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    private $id;

    private ?string $language = null;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $formAttributes;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     **/
    private $category;

    /**
     * @var string|null
     */
    private $cachedHtml;

    /**
     * @var string
     */
    private $postAction = 'message';

    /**
     * @var string|null
     */
    private $postActionProperty;

    /**
     * @var \DateTimeInterface
     */
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    private $publishDown;

    /**
     * @var ArrayCollection<int, Field>
     */
    private $fields;

    /**
     * @var ArrayCollection<string, Action>
     */
    private $actions;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var bool|null
     */
    private $inKioskMode = false;

    /**
     * @var bool|null
     */
    private $renderStyle = false;

    /**
     * @var Collection<int, Submission>
     */
    private Collection $submissions;

    /**
     * @var int
     */
    public $submissionCount;

    /**
     * @var string|null
     */
    private $formType;

    /**
     * @var bool|null
     */
    private $noIndex;

    /**
     * @var int|null
     */
    private $progressiveProfilingLimit;

    /**
     * This var is used to cache the result once gained from the loop.
     *
     * @var bool
     */
    private $usesProgressiveProfiling;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public function __construct()
    {
        $this->fields      = new ArrayCollection();
        $this->actions     = new ArrayCollection();
        $this->submissions = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('forms')
            ->setCustomRepositoryClass(FormRepository::class);

        $builder->addIdColumns();

        $builder->addField('alias', 'string');

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->nullable()
            ->build();

        $builder->addNullableField('formAttributes', 'string', 'form_attr');

        $builder->addCategory();

        $builder->createField('cachedHtml', 'text')
            ->columnName('cached_html')
            ->nullable()
            ->build();

        $builder->createField('postAction', 'string')
            ->columnName('post_action')
            ->build();

        $builder->createField('postActionProperty', Types::TEXT)
            ->columnName('post_action_property')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->createOneToMany('fields', 'Field')
            ->setIndexBy('id')
            ->setOrderBy(['order' => 'ASC'])
            ->mappedBy('form')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('actions', 'Action')
            ->setIndexBy('id')
            ->setOrderBy(['order' => 'ASC'])
            ->mappedBy('form')
            ->cascadeAll()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('template', 'string')
            ->nullable()
            ->build();

        $builder->createField('inKioskMode', 'boolean')
            ->columnName('in_kiosk_mode')
            ->nullable()
            ->build();

        $builder->createField('renderStyle', 'boolean')
            ->columnName('render_style')
            ->nullable()
            ->build();

        $builder->createOneToMany('submissions', 'Submission')
            ->setOrderBy(['dateSubmitted' => 'DESC'])
            ->mappedBy('form')
            ->fetchExtraLazy()
            ->build();

        $builder->addNullableField('formType', 'string', 'form_type');

        $builder->createField('noIndex', 'boolean')
            ->columnName('no_index')
            ->nullable()
            ->build();

        $builder->addNullableField('progressiveProfilingLimit', Types::INTEGER, 'progressive_profiling_limit');

        static::addUuidField($builder);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
            'groups'  => ['form'],
        ]));

        $metadata->addPropertyConstraint('postActionProperty', new Assert\NotBlank([
            'message' => 'mautic.form.form.postactionproperty_message.notblank',
            'groups'  => ['messageRequired'],
        ]));

        $metadata->addPropertyConstraint('postActionProperty', new Assert\NotBlank([
            'message' => 'mautic.form.form.postactionproperty_redirect.notblank',
            'groups'  => ['urlRequired'],
        ]));

        $metadata->addPropertyConstraint('postActionProperty', new Assert\Url([
            'message' => 'mautic.form.form.postactionproperty_redirect.notblank',
            'groups'  => ['urlRequiredPassTwo'],
        ]));

        $metadata->addPropertyConstraint('formType', new Assert\Choice([
            'choices' => ['standalone', 'campaign'],
        ]));

        $metadata->addPropertyConstraint('progressiveProfilingLimit', new Assert\GreaterThan([
            'value'   => 0,
            'message' => 'mautic.form.form.progressive_profiling_limit.error',
            'groups'  => ['progressiveProfilingLimit'],
        ]));
    }

    public static function determineValidationGroups(\Symfony\Component\Form\Form $form): array
    {
        $data   = $form->getData();
        $groups = ['form'];

        $postAction = $data->getPostAction();

        if ('message' == $postAction) {
            $groups[] = 'messageRequired';
        } elseif ('redirect' == $postAction) {
            $groups[] = 'urlRequired';
        }

        if ('' != $data->getProgressiveProfilingLimit()) {
            $groups[] = 'progressiveProfilingLimit';
        }

        return $groups;
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('form')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'alias',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'cachedHtml',
                    'publishUp',
                    'publishDown',
                    'fields',
                    'actions',
                    'template',
                    'inKioskMode',
                    'renderStyle',
                    'formType',
                    'postAction',
                    'postActionProperty',
                    'noIndex',
                    'formAttributes',
                    'language',
                ]
            )
            ->build();
    }

    protected function isChanged($prop, $val)
    {
        if ('actions' == $prop || 'fields' == $prop) {
            // changes are already computed so just add them
            $this->changes[$prop][$val[0]] = $val[1];
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Form
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     *
     * @return Form
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription($truncate = false, $length = 45)
    {
        if ($truncate) {
            if (strlen($this->description) > $length) {
                return substr($this->description, 0, $length).'...';
            }
        }

        return $this->description;
    }

    /**
     * @param string $cachedHtml
     *
     * @return Form
     */
    public function setCachedHtml($cachedHtml)
    {
        $this->cachedHtml = $cachedHtml;

        return $this;
    }

    /**
     * @return string
     */
    public function getCachedHtml()
    {
        return $this->cachedHtml;
    }

    /**
     * @return bool|null
     */
    public function getRenderStyle()
    {
        return $this->renderStyle;
    }

    /**
     * @param string $postAction
     *
     * @return Form
     */
    public function setPostAction($postAction)
    {
        $this->isChanged('postAction', $postAction);
        $this->postAction = $postAction;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostAction()
    {
        return $this->postAction;
    }

    /**
     * @param string $postActionProperty
     *
     * @return Form
     */
    public function setPostActionProperty($postActionProperty)
    {
        $this->isChanged('postActionProperty', $postActionProperty);
        $this->postActionProperty = $postActionProperty;

        return $this;
    }

    public function getPostActionProperty(): ?string
    {
        if ('return' === $this->getPostAction()) {
            return null;
        }

        return $this->postActionProperty;
    }

    public function getResultCount(): int
    {
        return count($this->submissions);
    }

    /**
     * @param \DateTime $publishUp
     *
     * @return Form
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param \DateTime $publishDown
     *
     * @return Form
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param int|string $key
     *
     * @return Form
     */
    public function addField($key, Field $field)
    {
        if ($changes = $field->getChanges()) {
            $this->isChanged('fields', [$key, $changes]);
        }
        $this->fields[$key] = $field;

        return $this;
    }

    /**
     * @param int|string $key
     */
    public function removeField($key, Field $field): void
    {
        if ($changes = $field->getChanges()) {
            $this->isChanged('fields', [$key, $changes]);
        }
        $this->fields->removeElement($field);
    }

    /**
     * @return ArrayCollection<int, Field>
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function getFieldAliases(): array
    {
        $aliases = [];
        $fields  = $this->getFields();

        if ($fields) {
            foreach ($fields as $field) {
                $aliases[] = $field->getAlias();
            }
        }

        return $aliases;
    }

    /**
     * Loops through the form fields and returns an array of fields with mapped data.
     *
     * @return array<int, array<string, int|string>>
     */
    public function getMappedFieldValues(): array
    {
        return array_filter(
            array_map(
                fn (Field $field): array => [
                    'formFieldId'  => $field->getId(),
                    'mappedObject' => $field->getMappedObject(),
                    'mappedField'  => $field->getMappedField(),
                ],
                $this->getFields()->getValues()
            ),
            fn ($elem) => isset($elem['mappedObject']) && isset($elem['mappedField'])
        );
    }

    /**
     * Set alias.
     * Loops trough the form fields and returns a simple array of mapped object keys if any.
     *
     * @return string[]
     */
    public function getMappedFieldObjects(): array
    {
        return array_values(
            array_filter(
                array_unique(
                    $this->getFields()->map(
                        fn (Field $field) => $field->getMappedObject()
                    )->toArray()
                )
            )
        );
    }

    /**
     * @param string $alias
     *
     * @return Form
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return Form
     */
    public function addSubmission(Submission $submissions)
    {
        $this->submissions[] = $submissions;

        return $this;
    }

    public function removeSubmission(Submission $submissions): void
    {
        $this->submissions->removeElement($submissions);
    }

    /**
     * @return Collection|Submission[]
     */
    public function getSubmissions()
    {
        return $this->submissions;
    }

    /**
     * @param int|string $key
     *
     * @return Form
     */
    public function addAction($key, Action $action)
    {
        if ($changes = $action->getChanges()) {
            $this->isChanged('actions', [$key, $changes]);
        }
        $this->actions[$key] = $action;

        return $this;
    }

    public function removeAction(Action $action): void
    {
        $this->actions->removeElement($action);
    }

    /**
     * Removes all actions.
     */
    public function clearActions(): void
    {
        $this->actions = new ArrayCollection();
    }

    /**
     * @return ArrayCollection<string, Action>
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param mixed $template
     */
    public function setTemplate($template): void
    {
        $this->template = $template;
    }

    /**
     * @return mixed
     */
    public function getInKioskMode()
    {
        return $this->inKioskMode;
    }

    /**
     * @param mixed $inKioskMode
     */
    public function setInKioskMode($inKioskMode): void
    {
        $this->inKioskMode = $inKioskMode;
    }

    /**
     * @param mixed $renderStyle
     */
    public function setRenderStyle($renderStyle): void
    {
        $this->renderStyle = $renderStyle;
    }

    /**
     * @return mixed
     */
    public function isInKioskMode()
    {
        return $this->getInKioskMode();
    }

    /**
     * @return mixed
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param mixed $formType
     *
     * @return Form
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;

        return $this;
    }

    /**
     * @param bool|null $noIndex
     */
    public function setNoIndex($noIndex): void
    {
        $sanitizedNoIndex = null === $noIndex ? null : (bool) $noIndex;
        $this->isChanged('noIndex', $sanitizedNoIndex);
        $this->noIndex = $sanitizedNoIndex;
    }

    /**
     * @return bool|null
     */
    public function getNoIndex()
    {
        return $this->noIndex;
    }

    /**
     * @param string $formAttributes
     *
     * @return Form
     */
    public function setFormAttributes($formAttributes)
    {
        $this->isChanged('formAttributes', $formAttributes);
        $this->formAttributes = $formAttributes;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormAttributes()
    {
        return $this->formAttributes;
    }

    public function setLanguage(?string $language): self
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function isStandalone(): bool
    {
        return 'campaign' != $this->formType;
    }

    /**
     * Generate a form name for HTML attributes.
     */
    public function generateFormName(): string
    {
        return $this->name ? strtolower(InputHelper::alphanum(InputHelper::transliterate($this->name))) : 'form-'.$this->id;
    }

    /**
     * Check if some Progressive Profiling setting is turned on on any of the form fields.
     *
     * @return bool
     */
    public function usesProgressiveProfiling()
    {
        if (null !== $this->usesProgressiveProfiling) {
            return $this->usesProgressiveProfiling;
        }

        // Progressive profiling must be turned off in the kiosk mode
        if (false === $this->getInKioskMode()) {
            if ('' != $this->getProgressiveProfilingLimit()) {
                $this->usesProgressiveProfiling = true;

                return $this->usesProgressiveProfiling;
            }

            // Search for a field with a progressive profiling setting on
            foreach ($this->fields->toArray() as $field) {
                if (false === $field->getShowWhenValueExists() || $field->getShowAfterXSubmissions() > 0) {
                    $this->usesProgressiveProfiling = true;

                    return $this->usesProgressiveProfiling;
                }
            }
        }

        $this->usesProgressiveProfiling = false;

        return $this->usesProgressiveProfiling;
    }

    /**
     * @param int $progressiveProfilingLimit
     *
     * @return Form
     */
    public function setProgressiveProfilingLimit($progressiveProfilingLimit)
    {
        $this->isChanged('progressiveProfilingLimit', $progressiveProfilingLimit);
        $this->progressiveProfilingLimit = $progressiveProfilingLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getProgressiveProfilingLimit()
    {
        return $this->progressiveProfilingLimit;
    }
}
