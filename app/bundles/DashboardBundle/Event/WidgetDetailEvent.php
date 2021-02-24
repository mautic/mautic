<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Event;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Exception\CouldNotFormatDateTimeException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WidgetDetailEvent.
 */
class WidgetDetailEvent extends CommonEvent
{
    /** @var Widget */
    protected $widget;
    protected $type;
    protected $template;
    protected $templateData = [];
    protected $errorMessage;
    protected $uniqueId;
    protected $cacheDir;
    protected $uniqueCacheDir;
    protected $cacheTimeout;
    protected $startTime = 0;
    protected $loadTime  = 0;
    protected $translator;

    private $cacheKeyPath = 'dashboard.widget.';

    /**
     * @var CorePermissions
     */
    protected $security = null;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * WidgetDetailEvent constructor.
     */
    public function __construct(TranslatorInterface $translator, CacheProvider $cacheProvider = null)
    {
        $this->translator    = $translator;
        $this->startTime     = microtime(true);
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Return unique key, uses legacy methods for BC.
     *
     * @return string
     */
    public function getCacheKey()
    {
        $cacheKey = [
            $this->getUniqueWidgetId(),
        ];

        $params = $this->getWidget()->getParams();

        foreach (['dateTo', 'dateFrom'] as $dateParameter) {
            if (isset($params[$dateParameter])) {
                try {
                    $date       = $this->castDateTimeToString($params[$dateParameter]);
                    $cacheKey[] = $date;
                } catch (CouldNotFormatDateTimeException $e) {
                }
            }
        }

        // If there are no additional parameters we return uniqueWidgetId as a cache key
        // Otherwise we return hashed $cacheKey value
        $cacheKey = (1 == count($cacheKey)) ? $this->getUniqueWidgetId() : substr(md5(implode('', $cacheKey)), 0, 16);

        return $this->cacheKeyPath.$cacheKey;
    }

    /**
     * Set the cache dir.
     *
     * @deprecated
     *
     * @param string $cacheDir
     * @param null   $uniqueCacheDir
     */
    public function setCacheDir($cacheDir, $uniqueCacheDir = null)
    {
        $this->cacheDir       = $cacheDir;
        $this->uniqueCacheDir = $uniqueCacheDir;
    }

    /**
     * Set the cache timeout.
     *
     * @deprecated
     *
     * @param string $cacheTimeout
     */
    public function setCacheTimeout($cacheTimeout)
    {
        $this->cacheTimeout = (int) $cacheTimeout;
    }

    /**
     * Set the widget type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the widget type.
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the widget entity.
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

        if (!isset($params['filter'])) {
            $params['filter'] = [];
        }

        $widget->setParams($params);

        $this->setType($widget->getType());
        $this->setCacheTimeout($widget->getCacheTimeout());
    }

    /**
     * Returns the widget entity.
     *
     * @return Widget $widget
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * Set the widget template.
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        $this->widget->setTemplate($template);
    }

    /**
     * Get the widget template.
     *
     * @return string $template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set the widget template data.
     * 
     * @param array $templateData
     * @param bool  $skipCache
     *
     * @return bool
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setTemplateData(array $templateData, $skipCache = false)
    {
        $this->templateData = $templateData;
        $this->widget->setTemplateData($templateData);
        $this->widget->setLoadTime(abs(microtime(true) - $this->startTime));

        if ($this->usesLegacyCache()) {
            // Store the template data to the cache
            if (!$skipCache && $this->cacheDir && $this->widget->getCacheTimeout() > 0) {
                $cache = new CacheStorageHelper(CacheStorageHelper::ADAPTOR_FILESYSTEM, $this->uniqueCacheDir, null, $this->cacheDir);
                // must pass a DateTime object or a int of seconds to expire as 3rd attribute to set().
                $expireTime = $this->widget->getCacheTimeout() * 60;
                return $cache->set($this->getUniqueWidgetId(), $templateData, (int) $expireTime);
            }

            return false;
        }

        $cItem = $this->cacheProvider->getItem($this->getCacheKey());
        if ($this->widget->getCacheTimeout()) {
            $cItem->expiresAfter((int) $this->widget->getCacheTimeout() * 60);  // This is in minutes
        }
        $cItem->set($templateData);

        return $this->cacheProvider->save($cItem);
    }

    /**
     * Get the widget template data.
     *
     * @return array $templateData
     */
    public function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * Set en error message.
     *
     * @param array $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        $this->widget->setErrorMessage($errorMessage);
    }

    /**
     * Get an error message.
     *
     * @return string $errorMessage
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Build a unique ID from type and widget params.
     *
     * @return string
     */
    public function getUniqueWidgetId()
    {
        if ($this->uniqueId) {
            return $this->uniqueId;
        }

        $params = $this->getWidget()->getParams();
        // Unset dateFrom and dateTo since they constantly change
        unset($params['dateFrom'], $params['dateTo']);

        $uniqueSettings = [
            'params' => $params,
            'width'  => $this->getWidget()->getWidth(),
            'height' => $this->getWidget()->getHeight(),
            'locale' => $this->translator->getLocale(),
        ];

        return $this->uniqueId = $this->getType().'_'.substr(md5(json_encode($uniqueSettings)), 0, 16);
    }

    /**
     * @return bool
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isCached()
    {
        if (!$this->cacheDir && $this->usesLegacyCache()) {
            return false;
        }

        if ($this->usesLegacyCache()) {
            $cache = new CacheStorageHelper(CacheStorageHelper::ADAPTOR_FILESYSTEM, $this->uniqueCacheDir, null, $this->cacheDir);
            $data  = $cache->get($this->getUniqueWidgetId(), $this->cacheTimeout);

            if ($data) {
                $this->widget->setCached(true);
                $this->setTemplateData($data, true);

                return true;
            }

            return false;
        }

        return ($this->cacheProvider->getItem($this->getCacheKey()))->isHit();
    }

    /**
     * Get the Translator object.
     *
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Set security object to check the perimissions.
     */
    public function setSecurity(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * Check if the user has at least one permission of defined array of permissions.
     *
     * @return bool
     */
    public function hasPermissions(array $permissions)
    {
        if (!$this->security) {
            return true;
        }
        $perm = $this->security->isGranted($permissions, 'RETURN_ARRAY');

        return in_array(true, $perm);
    }

    /**
     * Check if the user has defined permission to see the widgets.
     *
     * @param string $permission
     *
     * @return bool
     */
    public function hasPermission($permission)
    {
        if (!$this->security) {
            return true;
        }

        return $this->security->isGranted($permission);
    }

    /**
     * Checks for cache type. This event should be created by factory thus not legacy approach.
     *
     * @return bool
     */
    private function usesLegacyCache()
    {
        return is_null($this->cacheProvider);
    }

    /**
     * We need to cast DateTime objects to strings to use them in the cache key.
     *
     * @param \DateTimeInterface|mixed $value
     *
     * @throws CouldNotFormatDateTimeException
     *
     * @return string
     */
    private function castDateTimeToString($value)
    {
        if ($value instanceof \DateTimeInterface) {
            // We use RFC 2822 format because it includes timezone
            $value = $value->format('r');
            if (false === $value) {
                throw new CouldNotFormatDateTimeException();
            }

            return $value;
        }

        try {
            $value = (string) $value;
        } catch (\Exception $e) {
            throw new CouldNotFormatDateTimeException();
        }

        return $value;
    }
}
