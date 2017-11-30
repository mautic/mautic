<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Page.
 */
class Page extends FormEntity implements TranslationEntityInterface, VariantEntityInterface
{
    use TranslationEntityTrait;
    use VariantEntityTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $customHtml;

    /**
     * @var array
     */
    private $content = [];

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var int
     */
    private $hits = 0;

    /**
     * @var int
     */
    private $uniqueHits = 0;

    /**
     * @var int
     */
    private $variantHits = 0;

    /**
     * @var int
     */
    private $revision = 1;

    /**
     * @var string
     */
    private $metaDescription;

    /**
     * @var string
     */
    private $redirectType;

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var bool
     */
    private $isPreferenceCenter;

    /**
     * Used to identify the page for the builder.
     *
     * @var
     */
    private $sessionId;

    public function __clone()
    {
        $this->id = null;
        $this->clearTranslations();
        $this->clearVariants();

        parent::__clone();
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translationChildren = new \Doctrine\Common\Collections\ArrayCollection();
        $this->variantChildren     = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('pages')
            ->setCustomRepositoryClass('Mautic\PageBundle\Entity\PageRepository')
            ->addIndex(['alias'], 'page_alias_search');

        $builder->addId();

        $builder->addField('title', 'string');

        $builder->addField('alias', 'string');

        $builder->addNullableField('template', 'string');

        $builder->createField('customHtml', 'text')
            ->columnName('custom_html')
            ->nullable()
            ->build();

        $builder->createField('content', 'array')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->addField('hits', 'integer');

        $builder->createField('uniqueHits', 'integer')
            ->columnName('unique_hits')
            ->build();

        $builder->createField('variantHits', 'integer')
            ->columnName('variant_hits')
            ->build();

        $builder->addField('revision', 'integer');

        $builder->createField('metaDescription', 'string')
            ->columnName('meta_description')
            ->nullable()
            ->build();

        $builder->createField('redirectType', 'string')
            ->columnName('redirect_type')
            ->nullable()
            ->length(100)
            ->build();

        $builder->createField('redirectUrl', 'string')
            ->columnName('redirect_url')
            ->nullable()
            ->length(100)
            ->build();

        $builder->addCategory();

        $builder->createField('isPreferenceCenter', 'boolean')
            ->columnName('is_preference_center')
            ->nullable()
            ->build();

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new NotBlank([
            'message' => 'mautic.core.title.required',
        ]));

        $metadata->addConstraint(new Callback([
            'callback' => function (Page $page, ExecutionContextInterface $context) {
                $type = $page->getRedirectType();
                if (!is_null($type)) {
                    $validator = $context->getValidator();
                    $violations = $validator->validate($page->getRedirectUrl(), [
                        new Assert\Url(
                            [
                                'message' => 'mautic.core.value.required',
                            ]
                        ),
                    ]);

                    if (count($violations) > 0) {
                        $string = (string) $violations;
                        $context->buildViolation($string)
                            ->atPath('redirectUrl')
                            ->addViolation();
                    }
                }

                if ($page->isVariant()) {
                    // Get a summation of weights
                    $parent = $page->getVariantParent();
                    $children = $parent ? $parent->getVariantChildren() : $page->getVariantChildren();

                    $total = 0;
                    foreach ($children as $child) {
                        $settings = $child->getVariantSettings();
                        $total += (int) $settings['weight'];
                    }

                    if ($total > 100) {
                        $context->buildViolation('mautic.core.variant_weights_invalid')
                            ->atPath('variantSettings[weight]')
                            ->addViolation();
                    }
                }
            },
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('page')
            ->addListProperties(
                [
                    'id',
                    'title',
                    'alias',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'language',
                    'publishUp',
                    'publishDown',
                    'hits',
                    'uniqueHits',
                    'variantHits',
                    'revision',
                    'metaDescription',
                    'redirectType',
                    'redirectUrl',
                    'isPreferenceCenter',
                    'variantSettings',
                    'variantStartDate',
                    'variantParent',
                    'variantChildren',
                    'translationParent',
                    'translationChildren',
                    'template',
                    'customHtml',
                ]
            )
            ->setMaxDepth(1, 'variantParent')
            ->setMaxDepth(1, 'variantChildren')
            ->setMaxDepth(1, 'translationParent')
            ->setMaxDepth(1, 'translationChildren')
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Page
     */
    public function setTitle($title)
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set alias.
     *
     * @param string $alias
     *
     * @return Page
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Page
     */
    public function setContent($content)
    {
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     *
     * @return Page
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTime $publishDown
     *
     * @return Page
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * Set hits.
     *
     * @param int $hits
     *
     * @return Page
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits.
     *
     * @param bool $includeVariants
     *
     * @return int|mixed
     */
    public function getHits($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getHits') : $this->hits;
    }

    /**
     * Set revision.
     *
     * @param int $revision
     *
     * @return Page
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Get revision.
     *
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set metaDescription.
     *
     * @param string $metaDescription
     *
     * @return Page
     */
    public function setMetaDescription($metaDescription)
    {
        $this->isChanged('metaDescription', $metaDescription);
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * Get metaDescription.
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * Set redirectType.
     *
     * @param string $redirectType
     *
     * @return Page
     */
    public function setRedirectType($redirectType)
    {
        $this->isChanged('redirectType', $redirectType);
        $this->redirectType = $redirectType;

        return $this;
    }

    /**
     * Get redirectType.
     *
     * @return string
     */
    public function getRedirectType()
    {
        return $this->redirectType;
    }

    /**
     * Set redirectUrl.
     *
     * @param string $redirectUrl
     *
     * @return Page
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->isChanged('redirectUrl', $redirectUrl);
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Get redirectUrl.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Set category.
     *
     * @param \Mautic\CategoryBundle\Entity\Category $category
     *
     * @return Page
     */
    public function setCategory(Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Mautic\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set isPreferenceCenter.
     *
     * @param bool $isPreferenceCenter
     *
     * @return Page
     */
    public function setIsPreferenceCenter($isPreferenceCenter)
    {
        $this->isChanged('isPreferenceCenter', $isPreferenceCenter);
        $this->isPreferenceCenter = $isPreferenceCenter;

        return $this;
    }

    /**
     * Get isPreferenceCenter.
     *
     * @return bool
     */
    public function getIsPreferenceCenter()
    {
        return $this->isPreferenceCenter;
    }

    /**
     * Set sessionId.
     *
     * @param string $id
     *
     * @return Page
     */
    public function setSessionId($id)
    {
        $this->sessionId = $id;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set template.
     *
     * @param string $template
     *
     * @return Page
     */
    public function setTemplate($template)
    {
        $this->isChanged('template', $template);
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ($prop == 'translationParent' || $prop == 'variantParent' || $prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * Set uniqueHits.
     *
     * @param int $uniqueHits
     *
     * @return Page
     */
    public function setUniqueHits($uniqueHits)
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }

    /**
     * Get uniqueHits.
     *
     * @return int
     */
    public function getUniqueHits($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getUniqueHits') : $this->uniqueHits;
    }

    /**
     * @param bool $includeVariants
     *
     * @return int|mixed
     */
    public function getVariantHits($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getVariantHits') : $this->variantHits;
    }

    /**
     * @param mixed $variantHits
     */
    public function setVariantHits($variantHits)
    {
        $this->variantHits = $variantHits;
    }

    /**
     * @return mixed
     */
    public function getCustomHtml()
    {
        return $this->customHtml;
    }

    /**
     * @param mixed $customHtml
     */
    public function setCustomHtml($customHtml)
    {
        $this->customHtml = $customHtml;
    }
}
