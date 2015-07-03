<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Category
 * @ORM\Table(name="categories")
 * @ORM\Entity(repositoryClass="Mautic\CategoryBundle\Entity\CategoryRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Category extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"categoryDetails", "categoryList"})
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"categoryDetails", "categoryList"})
     */
    private $title;

    /**
     * @ORM\Column(name="alias", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"categoryDetails", "categoryList"})
     */
    private $alias;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"categoryDetails"})
     */
    private $description;

    /**
     * @ORM\Column(name="color", type="string", nullable=true, length=7)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"categoryDetails"})
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $bundle;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('title', new NotBlank(array(
            'message' => 'mautic.core.title.required'
        )));
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->id = null;

        parent::__clone();
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
     * @return Category
     */
    public function setTitle($title)
    {
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
     * @return Category
     */
    public function setAlias($alias)
    {
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
     * Set description
     *
     * @param string $description
     *
     * @return Category
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return Category
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set bundle
     *
     * @param string $bundle
     *
     * @return Category
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * Get bundle
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
