<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DashboardBundle\Entity\Widget;

/**
 * Class WidgetDetailEvent
 *
 * @package Mautic\DashboardBundle\Event
 */
class WidgetDetailEvent extends CommonEvent
{
    protected $widget;
    protected $type;
    protected $template;
    protected $templateData = array();
    protected $errorMessage;
    protected $uniqueId;

    /**
     * Set the widget type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the widget type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the widget entity
     *
     * @param Widget $widget
     */
    public function setWidget(Widget $widget)
    {
        $this->widget = $widget;
    }

    /**
     * Returns the widget entity
     *
     * @param Widget $widget
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * Set the widget template
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Get the widget template
     *
     * @return string $template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set the widget template data
     *
     * @param array  $templateData
     */
    public function setTemplateData(array $templateData)
    {
        $this->templateData = $templateData;
    }

    /**
     * Get the widget template data
     *
     * @return string $templateData
     */
    public function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * Set en error message
     *
     * @param array  $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get an error message
     *
     * @return string $errorMessage
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Build a unique ID from type and widget params
     *
     * @return string
     */
    public function getUniqueWidgetId()
    {
        if ($this->uniqueId) {
            return $this->uniqueId;
        }

        return $this->uniqueId = $this->getType() . '_' . substr(md5(json_encode($this->getWidget()->getParams())), 0, 16);
    }
}
