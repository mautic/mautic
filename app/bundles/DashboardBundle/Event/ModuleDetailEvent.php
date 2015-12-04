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
use Mautic\DashboardBundle\Entity\Module;

/**
 * Class ModuleDetailEvent
 *
 * @package Mautic\DashboardBundle\Event
 */
class ModuleDetailEvent extends CommonEvent
{
    protected $module;
    protected $type;
    protected $template;
    protected $templateData = array();
    protected $errorMessage;

    /**
     * Set the module type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the module type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the module entity
     *
     * @param Module $module
     */
    public function setModule(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Returns the module entity
     *
     * @param Module $module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the module template
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Get the module template
     *
     * @return string $template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set the module template data
     *
     * @param array  $templateData
     */
    public function setTemplateData(array $templateData)
    {
        $this->templateData = $templateData;
    }

    /**
     * Get the module template data
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
}
