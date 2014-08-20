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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\Column(name="path", type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $path;

    /**
     * @Assert\File(maxSize="6000000")
     */
    private $file;

    private $uploadDir;

    private $fileType;

    /**
     * @ORM\Column(name="alias", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $alias;

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
     * @ORM\ManyToOne(targetEntity="Mautic\CategoryBundle\Entity\Category")
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
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
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
     * Set path
     *
     * @param string $path
     * @return Asset
     */
    public function setPath($path)
    {
        $this->isChanged('path', $path);
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
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
    public function setCategory(\Mautic\CategoryBundle\Entity\Category $category = null)
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

    public function upload()
    {

        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }

        // TODO: use the original file name here but you should
        // sanitize it at least to avoid any security issues

        // move takes the target directory and then the
        // target filename to move to
        $this->getFile()->move(
            $this->getUploadRootDir(),
            $this->getFile()->getClientOriginalName()
        );

        // set the path property to the filename where you've saved the file
        $this->path = $this->getFile()->getClientOriginalName();

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }

    /**
     * Returns absolut path to the file.
     * 
     * @return string
     */
    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    /**
     * Returns relative path to the file.
     * 
     * @return string
     */
    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$this->path;
    }

    /**
     * Returns absolut path to upload dir.
     * 
     * @return string
     */
    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../'.$this->getUploadDir();
    }

    /**
     * Returns relative path to upload dir.
     * 
     * @return string 
     */
    protected function getUploadDir()
    {
        if ($this->uploadDir) {
            return $this->uploadDir;
        } else {
            return 'assets/files';
        }
    }

    /**
     * Set uploadDir
     *
     * @param string $uploadDir
     * @return Asset
     */
    public function setUploadDir($uploadDir)
    {
        $this->uploadDir = $uploadDir;

        return $this;
    }

    /**
     * Returns file extension
     * 
     * @return string 
     */
    public function getFileType()
    {
        if ($this->loadFile() === null) {
            return '';
        }

        return $this->loadFile()->getExtension();
    }

    /**
     * Returns Font Awesome icon class based on file type.
     * 
     * @return string 
     */
    public function getIconClass()
    {
        $fileType = $this->getFileType();

        // return missing file icon if file type is empty
        if (!$fileType) {
            return 'fa fa-ban';
        }

        $fileTypes = $this->getFileExtensions();

        // Search for icon name by file extension.
        foreach ($fileTypes as $icon => $extensions) {
            if (in_array($fileType, $extensions)) {
                return 'fa fa-file-' . $icon . '-o';
            }
        }

        // File extension is unknown, display general file icon.
        return 'fa fa-file-o'; 
    }

    /**
     * Returns array of common extensions
     * 
     * @return string 
     */
    public function getFileExtensions()
    {
        return array(
            'excel' => array(
                'xlsx',
                'xlsm',
                'xlsb',
                'xltx',
                'xltm',
                'xls',
                'xlt'
                ),
            'word' => array(
                'doc',
                'docx',
                'docm',
                'dotx'
                ),
            'pdf' => array(
                'pdf'
                ),
            'audio' => array(
                'mp3'
                ),
            'archive' => array(
                'zip',
                'rar',
                'iso',
                'tar',
                'gz',
                '7z'
                ),
            'image' => array(
                'jpg',
                'jpeg',
                'png',
                'gif',
                'ico',
                'bmp',
                'psd'
                ),
            'text' => array(
                'txt',
                'pub'
                ),
            'code' => array(
                'php',
                'js',
                'json',
                'yaml',
                'xml',
                'html',
                'htm',
                'sql'
                ),
            'powerpoint' => array(
                'ppt',
                'pptx',
                'pptm',
                'xps',
                'potm',
                'potx',
                'pot',
                'pps',
                'odp'
                ),
            'video' => array(
                'wmv',
                'avi',
                'mp4',
                'mkv',
                'mpeg'
                )
            );
    }

    /**
     * Load a file from it's path.
     * 
     * @return Symfony\Component\HttpFoundation\File\File or null
     */
    public function loadFile()
    {
        if (!$this->getAbsolutePath())
        {
            return null;
        }

        try {
            $file = new File($this->getAbsolutePath());
        } catch (FileNotFoundException $e) {
            $file = null;
        }

        return $file;
    }
}
