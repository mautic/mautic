<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Page
 * @ORM\Table(name="pages")
 * @ORM\Entity(repositoryClass="Mautic\PageBundle\Entity\PageRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Page extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails", "pageList"})
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails", "pageList"})
     */
    private $title;

    /**
     * @ORM\Column(name="alias", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails", "pageList"})
     */
    private $alias;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $template;

    /**
     * @ORM\Column(name="lang", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $language = 'en';

    /**
     * @ORM\Column(name="custom_html", type="text", nullable=true)
     */
    private $customHtml;

    /**
     * @ORM\Column(name="content", type="array", nullable=true)
     */
    private $content = array();

    /**
     * @ORM\Column(name="publish_up", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $publishUp;

    /**
     * @ORM\Column(name="publish_down", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $publishDown;

    /**
     * @ORM\Column(name="hits", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $hits = 0;

    /**
     * @ORM\Column(name="unique_hits", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $uniqueHits = 0;

    /**
     * @ORM\Column(name="variant_hits", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $variantHits = 0;

    /**
     * @ORM\Column(name="revision", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $revision = 1;

    /**
     * @ORM\Column(name="meta_description", type="string", nullable=true, length=320)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $metaDescription;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CategoryBundle\Entity\Category")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails", "pageList"})
     **/
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="Page", mappedBy="translationParent", indexBy="id")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $translationChildren;

    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="translationChildren")
     * @ORM\JoinColumn(name="translation_parent_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $translationParent = null;

    /**
     * @ORM\OneToMany(targetEntity="Page", mappedBy="variantParent", indexBy="id")
     * @ORM\OrderBy({"isPublished" = "DESC"})
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $variantChildren;

    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="variantChildren")
     * @ORM\JoinColumn(name="variant_parent_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $variantParent = null;

    /**
     * @ORM\Column(name="variant_settings", type="array", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $variantSettings = array();

    /**
     * @ORM\Column(name="variant_start_date", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"pageDetails"})
     */
    private $variantStartDate;

    /**
     * Used to identify the page for the builder
     *
     * @var
     */
    private $sessionId;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translationChildren = new \Doctrine\Common\Collections\ArrayCollection();
        $this->variantChildren     = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new NotBlank(array(
            'message' => 'mautic.core.title.required'
        )));

        $metadata->addConstraint(new Callback(array(
            'callback' => 'translationParentValidation'
        )));
    }

    /**
     * Callback constraint to ensure that a translation parent is not an a/b test
     *
     * @param ExecutionContextInterface $context
     */
    public function translationParentValidation(ExecutionContextInterface $context)
    {
        $translationParent = $this->getTranslationParent();

        if ($translationParent !== null) {
            $parentsVariantParent = $translationParent->getVariantParent();
            if ($parentsVariantParent !== null) {
                $context->buildViolation('mautic.page.translationparent.notallowed')
                    ->atPath('translationParent')
                    ->addViolation();
            }
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
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
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set alias
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
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set content
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
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set publishUp
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
     * Get publishUp
     *
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown
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
     * Get publishDown
     *
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * Set hits
     *
     * @param integer $hits
     *
     * @return Page
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return integer
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set revision
     *
     * @param integer $revision
     *
     * @return Page
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Get revision
     *
     * @return integer
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Set metaDescription
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
     * Get metaDescription
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * Set language
     *
     * @param string $language
     *
     * @return Page
     */
    public function setLanguage($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set category
     *
     * @param \Mautic\CategoryBundle\Entity\Category $category
     *
     * @return Page
     */
    public function setCategory(\Mautic\CategoryBundle\Entity\Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Mautic\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set sessionId
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
     * Get sessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set template
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
     * Get template
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
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();

        if ($prop == 'translationParent' || $prop == 'variantParent' || $prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = array($currentId, $newId);
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * Add translationChildren
     *
     * @param Page $translationChildren
     *
     * @return Page
     */
    public function addTranslationChild(Page $translationChildren)
    {
        $this->translationChildren[] = $translationChildren;

        return $this;
    }

    /**
     * Remove translationChildren
     *
     * @param Page $translationChildren
     */
    public function removeTranslationChild(Page $translationChildren)
    {
        $this->translationChildren->removeElement($translationChildren);
    }

    /**
     * Get translationChildren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslationChildren()
    {
        return $this->translationChildren;
    }

    /**
     * Set translationParent
     *
     * @param Page $translationParent
     *
     * @return Page
     */
    public function setTranslationParent(Page $translationParent = null)
    {
        $this->isChanged('translationParent', $translationParent);
        $this->translationParent = $translationParent;

        return $this;
    }

    /**
     * Remove variant parent
     */
    public function removeVariantParent()
    {
        $this->isChanged('variantParent', '');
        $this->variantParent = null;
    }

    /**
     * Get translationParent
     *
     * @return Page
     */
    public function getTranslationParent()
    {
        return $this->translationParent;
    }

    /**
     * Add variantChildren
     *
     * @param Page $variantChildren
     *
     * @return Page
     */
    public function addVariantChild(Page $variantChildren)
    {
        $this->variantChildren[] = $variantChildren;

        return $this;
    }

    /**
     * Remove variantChildren
     *
     * @param Page $variantChildren
     */
    public function removeVariantChild(Page $variantChildren)
    {
        $this->variantChildren->removeElement($variantChildren);
    }

    /**
     * Get variantChildren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVariantChildren()
    {
        return $this->variantChildren;
    }

    /**
     * Set variantParent
     *
     * @param Page $variantParent
     *
     * @return Page
     */
    public function setVariantParent(Page $variantParent = null)
    {
        $this->isChanged('variantParent', $variantParent);
        $this->variantParent = $variantParent;

        return $this;
    }

    /**
     * Remove translation parent
     */
    public function removeTranslationParent()
    {
        $this->isChanged('translationParent', '');
        $this->translationParent = null;
    }

    /**
     * Get variantParent
     *
     * @return Page
     */
    public function getVariantParent()
    {
        return $this->variantParent;
    }

    /**
     * Set variantSettings
     *
     * @param array $variantSettings
     *
     * @return Page
     */
    public function setVariantSettings($variantSettings)
    {
        $this->isChanged('variantSettings', $variantSettings);
        $this->variantSettings = $variantSettings;

        return $this;
    }

    /**
     * Get variantSettings
     *
     * @return array
     */
    public function getVariantSettings()
    {
        return $this->variantSettings;
    }

    /**
     * Set uniqueHits
     *
     * @param integer $uniqueHits
     *
     * @return Page
     */
    public function setUniqueHits($uniqueHits)
    {
        $this->uniqueHits = $uniqueHits;

        return $this;
    }

    /**
     * Get uniqueHits
     *
     * @return integer
     */
    public function getUniqueHits()
    {
        return $this->uniqueHits;
    }

    /**
     * @return mixed
     */
    public function getVariantHits()
    {
        return $this->variantHits;
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
    public function getVariantStartDate()
    {
        return $this->variantStartDate;
    }

    /**
     * @param mixed $variantStartDate
     */
    public function setVariantStartDate($variantStartDate)
    {
        $this->isChanged('variantStartDate', $variantStartDate);
        $this->variantStartDate = $variantStartDate;
    }

    /**
     * @return mixed
     */
    public function getCustomHtml ()
    {
        return $this->customHtml;
    }

    /**
     * @param mixed $customHtml
     */
    public function setCustomHtml ($customHtml)
    {
        $this->customHtml = $customHtml;
    }
}
