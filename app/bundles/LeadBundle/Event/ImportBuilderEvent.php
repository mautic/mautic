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
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ImportEvent.
 */
class ImportBuilderEvent extends CommonEvent
{
    /**
     * @var string
     */
    private $objectInRequest;

    /**
     * @var string
     */
    private $object;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var FormModel
     */
    private $model;

    /**
     * ImportBuilderEvent constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->object = $request->get('object', 'contacts');
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
    public function getObjectInRequest()
    {
        return $this->objectInRequest;
    }

    /**
     * @param string $objectInRequest
     */
    public function setObjectInRequest($objectInRequest)
    {
        $this->objectInRequest = $objectInRequest;
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
}
