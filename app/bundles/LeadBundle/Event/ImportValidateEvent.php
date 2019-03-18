<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Import;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Form;

class ImportValidateEvent extends Event
{
    /**
     * @var string
     */
    private $routeObjectName;

    /**
     * @var bool
     */
    private $objectSupported;

    /**
     * @var Form
     */
    private $form;

    /**
     * @var array
     */
    private $matchedFields = [];

    /**
     * @var ?int
     */
    private $ownerId;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @var ?int
     */
    private $list;

    /**
     * @param string $routeObjectName
     * @param Form   $form
     */
    public function __construct($routeObjectName, Form $form)
    {
        $this->routeObjectName = $routeObjectName;
        $this->form            = $form;
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Check if the form we're validating has errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->form->getErrors());
    }

    /**
     * Check if the import is for said route object and notes if the object exist.
     *
     * @param string $routeObject
     *
     * @return bool
     */
    public function importIsForRouteObject($routeObject)
    {
        if ($this->getRouteObjectName() === $routeObject) {
            $this->objectSupported = true;

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getRouteObjectName()
    {
        return $this->routeObjectName;
    }

    /**
     * Set the matchedFields in the event.
     *
     * @param array $matchedFields
     */
    public function setMatchedFields(array $matchedFields)
    {
        $this->matchedFields = $matchedFields;
    }

    /**
     * @return array
     */
    public function getMatchedFields()
    {
        return $this->matchedFields;
    }

    /**
     * @param ?int $ownerId
     */
    public function setOwnerId($ownerId = null)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @return ?int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param ?int $list
     */
    public function setList($list = null)
    {
        $this->list = $list;
    }

    /**
     * @return ?int
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags = [])
    {
        $this->tags = $tags;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }
}
