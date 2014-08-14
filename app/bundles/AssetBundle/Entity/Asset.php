<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Asset
 * @ORM\Table(name="assets")
 * @ORM\Entity(repositoryClass="Mautic\AssetBundle\Entity\AssetRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Asset extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $title;

    /**
     * @ORM\Column(name="alias", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $alias;

    /**
     * @ORM\Column(type="string")
     */
    private $template;

    /**
     * @ORM\Column(name="author", type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $author;

    /**
     * @ORM\Column(name="lang", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $language = 'en';

    /**
     * @ORM\Column(name="content", type="array")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $content = array();

    /**
     * @ORM\Column(name="publish_up", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $publishUp;

    /**
     * @ORM\Column(name="publish_down", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $publishDown;

    /**
     * @ORM\Column(name="hits", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $hits = 0;

    /**
     * @ORM\Column(name="unique_hits", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $uniqueHits = 0;

    /**
     * @ORM\Column(name="revision", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $revision = 1;

    /**
     * @ORM\Column(name="meta_description", type="string", nullable=true, length=160)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $metaDescription;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="assets")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="Asset", mappedBy="translationParent", indexBy="id", fetch="EXTRA_LAZY")
     **/
    private $translationChildren;

    /**
     * @ORM\ManyToOne(targetEntity="Asset", inversedBy="translationChildren")
     * @ORM\JoinColumn(name="translation_parent_id", referencedColumnName="id")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $translationParent;

    /**
     * @ORM\OneToMany(targetEntity="Asset", mappedBy="variantParent", indexBy="id", fetch="EXTRA_LAZY")
     **/
    private $variantChildren;

    /**
     * @ORM\ManyToOne(targetEntity="Asset", inversedBy="variantChildren")
     * @ORM\JoinColumn(name="variant_parent_id", referencedColumnName="id")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $variantParent;

    /**
     * @ORM\Column(name="variant_settings", type="array", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $variantSettings = array();

    /**
     * Used to identify the page for the builder
     *
     * @var
     */
    private $sessionId;

    public function __clone() {
        $this->id = null;
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
     * @return Asset
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
     * @return Asset
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
     * @return Asset
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
     * @return Asset
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
     * @return Asset
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
     * @param \DateTime $hits
     * @return Asset
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return \DateTime
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set revision
     *
     * @param integer $revision
     * @return Asset
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
     * @return Asset
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
     * @return Asset
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
     * @param \Mautic\AssetBundle\Entity\Category $category
     * @return Asset
     */
    public function setCategory(\Mautic\AssetBundle\Entity\Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Mautic\AssetBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return Asset
     */
    public function setAuthor($author)
    {
        $this->isChanged('author', $author);
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set sessionId
     *
     * @param string $id
     * @return Asset
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
     * @return Asset
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

        if ($prop == 'translationParent' || $prop == 'variantParent') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = $val->getId();
            if ($currentId != $newId)
                $currentTitle = ($current) ? $current->getTitle() . " ($currentId)" : '';
                $this->changes[$prop] = array($currentTitle, $val->getTitle() . " ($newId)");
        } else {
            parent::isChanged($prop, $val);
        }
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translationChildren = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add translationChildren
     *
     * @param \Mautic\AssetBundle\Entity\Asset $translationChildren
     * @return Asset
     */
    public function addTranslationChild(\Mautic\AssetBundle\Entity\Asset $translationChildren)
    {
        $this->translationChildren[] = $translationChildren;

        return $this;
    }

    /**
     * Remove translationChildren
     *
     * @param \Mautic\AssetBundle\Entity\Asset $translationChildren
     */
    public function removeTranslationChild(\Mautic\AssetBundle\Entity\Asset $translationChildren)
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
     * @param \Mautic\AssetBundle\Entity\Asset $translationParent
     * @return Asset
     */
    public function setTranslationParent(\Mautic\AssetBundle\Entity\Asset $translationParent = null)
    {
        $this->isChanged('translationParent', $translationParent);
        $this->translationParent = $translationParent;

        return $this;
    }

    /**
     * Get translationParent
     *
     * @return \Mautic\AssetBundle\Entity\Asset
     */
    public function getTranslationParent()
    {
        return $this->translationParent;
    }

    /**
     * Add variantChildren
     *
     * @param \Mautic\AssetBundle\Entity\Asset $variantChildren
     * @return Asset
     */
    public function addVariantChild(\Mautic\AssetBundle\Entity\Asset $variantChildren)
    {
        $this->variantChildren[] = $variantChildren;

        return $this;
    }

    /**
     * Remove variantChildren
     *
     * @param \Mautic\AssetBundle\Entity\Asset $variantChildren
     */
    public function removeVariantChild(\Mautic\AssetBundle\Entity\Asset $variantChildren)
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
     * @param \Mautic\AssetBundle\Entity\Asset $variantParent
     * @return Asset
     */
    public function setVariantParent(\Mautic\AssetBundle\Entity\Asset $variantParent = null)
    {
        $this->isChanged('variantParent', $variantParent);
        $this->variantParent = $variantParent;

        return $this;
    }

    /**
     * Get variantParent
     *
     * @return \Mautic\AssetBundle\Entity\Asset
     */
    public function getVariantParent()
    {
        return $this->variantParent;
    }

    /**
     * Set variantSettings
     *
     * @param array $variantSettings
     * @return Asset
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
     * @return Asset
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
}
