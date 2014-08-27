<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\FormEntity;

/**
 * Class Report
 * @ORM\Table(name="reports")
 * @ORM\Entity(repositoryClass="Mautic\ReportBundle\Entity\ReportRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Report extends FormEntity
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
     * @ORM\Column(type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $system = false;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $source;

    /**
     * @ORM\Column(type="array")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $columns = array();

    /**
     * @ORM\Column(type="array")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $filters = array();

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
     * @return Report
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
     * Set system
     *
     * @param string $system
     * @return Report
     */
    public function setSystem($system)
    {
        $this->isChanged('system', $system);
        $this->system = $system;

        return $this;
    }

    /**
     * Get system
     *
     * @return integer
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Report
     */
    public function setSource($source)
    {
        $this->isChanged('source', $source);
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set columns
     *
     * @param string $columns
     * @return Report
     */
    public function setColumns($columns)
    {
        $this->isChanged('columns', $columns);
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get columns
     *
     * @return string
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set filters
     *
     * @param string $filters
     * @return Report
     */
    public function setFilters($filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters
     *
     * @return string
     */
    public function getFilters()
    {
        return $this->filters;
    }
}
