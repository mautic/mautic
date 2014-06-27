<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Form
 * @ORM\Table(name="forms")
 * @ORM\Entity(repositoryClass="Mautic\FormBundle\Entity\FormRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Form extends FormEntity
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
     * @ORM\Column(type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=25)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $alias;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $description;

    /**
     * @ORM\Column(name="is_published", type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $isPublished = true;

    /**
     * @ORM\Column(name="cached_html", type="text", nullable=true)
     */
    private $cachedHtml;

    /**
     * @ORM\Column(name="cached_js", type="text", nullable=true)
     */
    private $cachedJs;

    /**
     * @ORM\Column(name="post_action", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $postAction;

    /**
     * @ORM\Column(name="post_action_property", type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $postActionProperty;

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
     * @ORM\OneToMany(targetEntity="Field", mappedBy="form", cascade={"all"}, indexBy="id")
     * @ORM\OrderBy({"order" = "ASC"})
     */
    private $fields;

    /**
     * @ORM\OneToMany(targetEntity="Action", mappedBy="form", cascade={"all"}, indexBy="id", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"order" = "ASC"})
     */
    private $actions;

    /**
     * @ORM\OneToMany(targetEntity="Submission", mappedBy="form", cascade={"all"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"dateSubmitted" = "DESC"})
     */
    private $submissions;

    private $changes;

    private function isChanged($prop, $val)
    {
        if ($prop == 'actions' || $prop == 'fields') {
            //changes are already computed so just add them
            $this->changes[$prop][$val[0]] = $val[1];
        } elseif ($this->$prop != $val) {
            $this->changes[$prop] = array($this->$prop, $val);
        }
    }

    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(array(
            'message' => 'mautic.form.form.name.notblank',
            'groups'  => array('form')
        )));

        $metadata->addPropertyConstraint('postActionProperty', new Assert\NotBlank(array(
            'message' => 'mautic.form.form.postactionproperty_message.notblank',
            'groups'  => array('messageRequired')
        )));

        $metadata->addPropertyConstraint('postActionProperty', new Assert\NotBlank(array(
            'message' => 'mautic.form.form.postactionproperty_redirect.notblank',
            'groups'  => array('urlRequired')
        )));

        $metadata->addPropertyConstraint('postActionProperty', new Assert\Url(array(
            'message' => 'mautic.form.form.postactionproperty_redirect.notblank',
            'groups'  => array('urlRequiredPassTwo')
        )));
    }

    /**
     * @param Form $form
     * @return array
     */
    static public function determineValidationGroups(\Symfony\Component\Form\Form $form) {
        $data   = $form->getData();
        $groups = array('form');

        $postAction = $data->getPostAction();
        if ($postAction == 'message') {
            $groups[] = 'messageRequired';
        } elseif ($postAction == 'redirect') {
            $groups['urlRequired'];
            $groups['urlRequiredPassTwo'];
        }

        return $groups;
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
     * Set name
     *
     * @param string $name
     * @return Form
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
     * Set description
     *
     * @param string $description
     * @return Form
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
    public function getDescription($truncate = false, $length = 50)
    {
        if ($truncate) {
            if (strlen($this->description) > $length) {
                return substr($this->description, 0, $length) . "...";
            }
        }
        return $this->description;
    }

    /**
     * Set isPublished
     *
     * @param boolean $isPublished
     * @return Form
     */
    public function setIsPublished($isPublished)
    {
        $this->isChanged('isPublished', $isPublished);
        $this->isPublished = $isPublished;

        return $this;
    }

    /**
     * Get isPublished
     *
     * @return boolean
     */
    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     * Proxy function to getIsPublished()
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->getIsPublished();
    }

    /**
     * Set cachedHtml
     *
     * @param string $cachedHtml
     * @return Form
     */
    public function setCachedHtml($cachedHtml)
    {
        $this->cachedHtml = $cachedHtml;

        return $this;
    }

    /**
     * Get cachedHtml
     *
     * @return string
     */
    public function getCachedHtml()
    {
        return $this->cachedHtml;
    }

    /**
     * Set cachedJs
     *
     * @param string $cachedJs
     * @return Form
     */
    public function setCachedJs($cachedJs)
    {
        $this->cachedJs = $cachedJs;

        return $this;
    }

    /**
     * Get cachedJs
     *
     * @return string
     */
    public function getCachedJs()
    {
        return $this->cachedJs;
    }

    /**
     * Set postAction
     *
     * @param string $postAction
     * @return Form
     */
    public function setPostAction($postAction)
    {
        $this->isChanged('postAction', $postAction);
        $this->postAction = $postAction;

        return $this;
    }

    /**
     * Get postAction
     *
     * @return string
     */
    public function getPostAction()
    {
        return $this->postAction;
    }

    /**
     * Set postActionProperty
     *
     * @param string $postActionProperty
     * @return Form
     */
    public function setPostActionProperty($postActionProperty)
    {
        $this->isChanged('postActionProperty', $postActionProperty);
        $this->postActionProperty = $postActionProperty;

        return $this;
    }

    /**
     * Get postActionProperty
     *
     * @return string
     */
    public function getPostActionProperty()
    {
        return $this->postActionProperty;
    }

    /**
     * Get result count
     */
    public function getResultCount()
    {
        return count($this->submissions);
    }

    /**
     * Set publishUp
     *
     * @param \DateTime $publishUp
     * @return Form
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
     * @return Form
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
     * Constructor
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->actions = new ArrayCollection();
    }

    /**
     * Add fields
     *
     * @param $key
     * @param \Mautic\FormBundle\Entity\Field $field
     * @return Form
     */
    public function addField($key, Field $field)
    {
        if ($changes = $field->getChanges()) {
            $this->isChanged('fields', array($key, $changes));
        }
        $this->fields[$key] = $field;

        return $this;
    }

    /**
     * Remove fields
     *
     * @param \Mautic\FormBundle\Entity\Field $fields
     */
    public function removeField(\Mautic\FormBundle\Entity\Field $fields)
    {
        $this->fields->removeElement($fields);
    }

    /**
     * Get fields
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set alias
     *
     * @param string $alias
     * @return Form
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
     * Add submissions
     *
     * @param \Mautic\FormBundle\Entity\Submission $submissions
     * @return Form
     */
    public function addSubmission(\Mautic\FormBundle\Entity\Submission $submissions)
    {
        $this->submissions[] = $submissions;

        return $this;
    }

    /**
     * Remove submissions
     *
     * @param \Mautic\FormBundle\Entity\Submission $submissions
     */
    public function removeSubmission(\Mautic\FormBundle\Entity\Submission $submissions)
    {
        $this->submissions->removeElement($submissions);
    }

    /**
     * Get submissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubmissions()
    {
        return $this->submissions;
    }

    /**
     * Add actions
     *
     * @param $key
     * @param \Mautic\FormBundle\Entity\Action $actions
     * @return Form
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
}
