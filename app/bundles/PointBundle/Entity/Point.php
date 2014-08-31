<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Point
 * @ORM\Table(name="points")
 * @ORM\Entity(repositoryClass="Mautic\PointBundle\Entity\PointRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Point extends FormEntity
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
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $description;

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
     * @ORM\OneToMany(targetEntity="Action", mappedBy="point", cascade={"all"}, indexBy="id", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"order" = "ASC"})
     */
    private $actions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->actions = new ArrayCollection();
    }

    protected function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'actions') {
            //changes are already computed so just add them
            $this->changes[$prop][$val[0]] = $val[1];
        } elseif ($current != $val) {
            $this->changes[$prop] = array($current, $val);
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
     * Set description
     *
     * @param string $description
     * @return Action
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
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
     * Set name
     *
     * @param string $name
     * @return Action
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add actions
     *
     * @param $key
     * @param \Mautic\PointBundle\Entity\Action $actions
     * @return Point
     */
    public function addAction($key, Action $action)
    {
        if ($changes = $action->getChanges()) {
            $this->isChanged('actions', array($key, $changes));
        }
        $this->actions[$key] = $action;

        return $this;
    }

    /**
     * Remove actions
     *
     * @param \Mautic\FormBundle\Entity\Action $actions
     */
    public function removeAction(\Mautic\FormBundle\Entity\Action $actions)
    {
        $this->actions->removeElement($actions);
    }

    /**
     * Get actions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Set publishUp
     *
     * @param \DateTime $publishUp
     * @return Point
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
     * @return Point
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
}
