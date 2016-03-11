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
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Translation\Translator;

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
    protected $cacheDir;
    protected $cacheTimeout;
    protected $startTime = 0;
    protected $loadTime = 0;
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
        $this->startTime = microtime();
    }

    /**
     * Set the cache dir
     *
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Set the cache timeout
     *
     * @param string $cacheTimeout
     */
    public function setCacheTimeout($cacheTimeout)
    {
        $this->cacheTimeout = (int) $cacheTimeout;
    }

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

        $params = $widget->getParams();

        // Set required params if undefined
        if (!isset($params['timeUnit'])) {
            $params['timeUnit'] = null;
        }

        if (!isset($params['amount'])) {
            $params['amount'] = null;
        }

        if (!isset($params['dateFormat'])) {
            $params['dateFormat'] = null;
        }

        // Count the amount from the date range if the $dateFrom is provided
        if ($params['dateFrom']) {
            $from   = $params['dateFrom'];
            $to     = $params['dateTo'];
            $unit   = $params['timeUnit'];

            if ($params['timeUnit'] == 'd' || $params['timeUnit'] == 'W') {
                $unit = 'a';
                $diff = ($to->diff($from)->format('%' . $unit) + 1);
                $diff = $params['timeUnit'] == 'W' ? floor($diff / 7) : $diff;
            } elseif ($params['timeUnit'] == 'm') {
                $diff = $to->diff($from)->format('%y') * 12 + $to->diff($from)->format('%m');
                if ($to->diff($from)->format('%d') > 0) $diff++;
                if ($from->format('d') >= $to->format('d')) $diff++;
            } elseif ($params['timeUnit'] == 'H') {
                if ($from == $to) {
                    // a diff of two identical dates returns 0, but we expect 24 hours
                    $to->modify('+1 day');
                    $toClone = clone $to;
                    $params['dateTo'] = $toClone->modify('-1 second');
                }
                $dateDiff = $to->diff($from);
                $diff = $dateDiff->h + $dateDiff->days * 24;
            } else {
                $diff = ($to->diff($from)->format('%' . $unit) + 1);
            }

            $params['amount'] = $diff;
        }

        $widget->setParams($params);

        $this->setType($widget->getType());
        $this->setCacheTimeout($widget->getCacheTimeout());
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
        $this->widget->setTemplate($template);
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
    public function setTemplateData(array $templateData, $skipCache = false)
    {
        $this->templateData = $templateData;
        $this->widget->setTemplateData($templateData);
        $this->widget->setLoadTime(abs(microtime() - $this->startTime));

        // Store the template data to the cache
        if (!$skipCache && $this->cacheDir && $this->widget->getCacheTimeout() > 0) {
            $cache = new CacheStorageHelper($this->cacheDir);
            $cache->set($this->getUniqueWidgetId(), $templateData);
        }
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
        $this->widget->setErrorMessage($errorMessage);
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

        $uniqueSettings = array(
            'params' => $this->getWidget()->getParams(),
            'width' => $this->getWidget()->getWidth(),
            'height' => $this->getWidget()->getHeight(),
        );

        return $this->uniqueId = $this->getType() . '_' . substr(md5(json_encode($uniqueSettings)), 0, 16);
    }

    /**
     * Checks the cache for the widget data.
     * If cache exists, it sets the TemplateData.
     *
     * @return string
     */
    public function isCached()
    {
        if (!$this->cacheDir) {
            return false;
        }

        $cache = new CacheStorageHelper($this->cacheDir);
        $data  = $cache->get($this->getUniqueWidgetId(), $this->cacheTimeout);

        if ($data) {
            $this->widget->setCached(true);
            $this->setTemplateData($data, true);
            return true;
        }

        return false;
    }

    /**
     * Get the Translator object
     *
     * @return Translator $translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}
