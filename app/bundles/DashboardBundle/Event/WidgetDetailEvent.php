<?php

namespace Mautic\DashboardBundle\Event;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Exception\CouldNotFormatDateTimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetDetailEvent extends CommonEvent
{
    public const DASHBOARD_CACHE_TAG = 'dashboard_widget';

    protected $type;

    protected $template;

    protected $templateData = [];

    protected $errorMessage;

    protected $uniqueId;

    protected $cacheDir;

    protected $uniqueCacheDir;

    protected $cacheTimeout;

    protected float $startTime;

    protected $loadTime  = 0;

    private string $cacheKeyPath = 'dashboard.widget.';

    private bool $isPreview = false;

    public function __construct(private TranslatorInterface $translator, private CorePermissions $security, protected Widget $widget, private ?CacheProvider $cacheProvider = null)
    {
        $this->startTime = microtime(true);
        $this->setWidget($widget);
    }

    /**
     * Act as widget preview without data.
     */
    public function setPreview(bool $isPreview): void
    {
        $this->isPreview = $isPreview;
    }

    /**
     * Is preview without data?
     */
    public function isPreview(): bool
    {
        return $this->isPreview;
    }

    /**
     * Return unique key, uses legacy methods for BC.
     */
    public function getCacheKey(): string
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
                } catch (CouldNotFormatDateTimeException) {
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
     * @param string     $cacheDir
     * @param mixed|null $uniqueCacheDir
     */
    public function setCacheDir($cacheDir, $uniqueCacheDir = null): void
    {
        $this->cacheDir       = $cacheDir;
        $this->uniqueCacheDir = $uniqueCacheDir;
    }

    /**
     * Set the cache timeout.
     *
     * @param string $cacheTimeout
     */
    public function setCacheTimeout($cacheTimeout): void
    {
        $this->cacheTimeout = (int) $cacheTimeout;
    }

    /**
     * Set the widget type.
     *
     * @param string $type
     */
    public function setType($type): void
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
    public function setWidget(Widget $widget): void
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
    public function setTemplate($template): void
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
     * @param bool|null $skipCache
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function setTemplateData(array $templateData, $skipCache = false): void
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

                $cache->set($this->getUniqueWidgetId(), $templateData, (int) $expireTime);
            }
        }

        $cItem = $this->cacheProvider->getItem($this->getCacheKey());
        if ($this->widget->getCacheTimeout()) {
            $cItem->expiresAfter((int) $this->widget->getCacheTimeout() * 60);  // This is in minutes
        }
        $cItem->set($templateData);
        $cItem->tag(self::DASHBOARD_CACHE_TAG);

        $this->cacheProvider->save($cItem);
    }

    /**
     * Get the widget template data.
     *
     * @return array<mixed> $templateData
     */
    public function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * Set en error message.
     *
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage): void
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
     * @throws \Psr\Cache\InvalidArgumentException
     *                                             Checks the cache for the widget data.
     *                                             If cache exists, it sets the TemplateData.
     */
    public function isCached(): bool
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
        $cachedItem = $this->cacheProvider->getItem($this->getCacheKey());
        if (!$cachedItem->isHit()) {
            return false;
        }

        $this->widget->setCached(true);
        $this->setTemplateData($cachedItem->get());

        return true;
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
     *
     * @depreacated
     */
    public function setSecurity(CorePermissions $security): void
    {
        $this->security = $security;
    }

    /**
     * Check if the user has at least one permission of defined array of permissions.
     */
    public function hasPermissions(array $permissions): bool
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
     */
    private function usesLegacyCache(): bool
    {
        return is_null($this->cacheProvider);
    }

    /**
     * We need to cast DateTime objects to strings to use them in the cache key.
     *
     * @param mixed|null $value
     *
     * @throws CouldNotFormatDateTimeException
     */
    private function castDateTimeToString($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            // We use RFC 2822 format because it includes timezone
            return $value->format('r');
        }

        try {
            $value = strval($value);
        } catch (\Exception) {
            throw new CouldNotFormatDateTimeException();
        }

        return $value;
    }
}
