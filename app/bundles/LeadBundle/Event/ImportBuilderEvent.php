<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Import;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportEvent.
 */
class ImportBuilderEvent extends CommonEvent
{
    /**
     * @var string
     */
    private $objectFromRequest;

    /**
     * @var string
     */
    private $object;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var FormModel
     */
    private $model;

    /**
     * @var string
     */
    private $activeLink;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $route;

    /**
     * ImportBuilderEvent constructor.
     *
     * @param Request     $request
     * @param Import|null $import
     */
    public function __construct(Request $request = null, Import $import = null)
    {
        if ($import) {
            $this->object = $import->getObject();
        } elseif ($request) {
            $this->object = $request->get('object', 'contacts');
        }

        $this->setLabel('mautic.lead.list.view_'.$this->object);
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return string
     */
    public function getObjectFromRequest()
    {
        return $this->objectFromRequest;
    }

    /**
     * @param string $objectFromRequest
     */
    public function setObjectFromRequest($objectFromRequest)
    {
        $this->objectFromRequest = $objectFromRequest;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return FormModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param FormModel $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getActiveLink()
    {
        return $this->activeLink;
    }

    /**
     * @param string $activeLink
     */
    public function setActiveLink($activeLink)
    {
        $this->activeLink = $activeLink;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }
}
