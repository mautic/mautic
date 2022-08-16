<?php

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\Helper\Filesystem;
use ReflectionProperty;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    private Filesystem $filesystem;

    private string $cacheDir;

    public function __construct(Filesystem $filesystem, string $cacheDir)
    {
        $this->filesystem = $filesystem;
        $this->cacheDir   = $cacheDir;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE  => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE     => ['onConfigPreSave', 0],
            ConfigEvents::CONFIG_POST_SAVE    => ['onConfigPostSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm([
            'bundle'     => 'ApiBundle',
            'formAlias'  => 'apiconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticApiBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticApiBundle'),
        ]);
    }

    public function onConfigPreSave(ConfigEvent $event): void
    {
        // Symfony craps out with integer for firewall settings
        $data = $event->getConfig('apiconfig');
        if (isset($data['api_enable_basic_auth'])) {
            $data['api_enable_basic_auth'] = (bool) $data['api_enable_basic_auth'];
            $event->setConfig($data, 'apiconfig');
        }
    }

    public function onConfigPostSave(ConfigEvent $event): void
    {
        $data           = $event->getConfig('apiconfig');
        $originalConfig = $event->getOriginalNormData();

        if (1 !== $data['api_enabled'] || !isset($originalConfig['apiconfig']['parameters']['api_enabled']) || 0 !== $originalConfig['apiconfig']['parameters']['api_enabled']) {
            return;
        }

        // @todo in Symfony 5 replace with ['url_matching_routes', 'url_generating_routes']
        foreach (['UrlGenerator', 'UrlMatcher'] as $class) {
            $oldCachePath = $this->cacheDir.'/'.$class.'.php';

            if (!$this->filesystem->exists($oldCachePath)) {
                continue;
            }

            $this->filesystem->remove($oldCachePath);

            $refProperty = new ReflectionProperty(\Symfony\Component\Routing\Router::class, 'cache');
            $refProperty->setAccessible(true);
            $currentCache = $refProperty->getValue();
            unset($currentCache[$oldCachePath]);
            $refProperty->setValue(null, $currentCache);

            if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
                @opcache_invalidate($oldCachePath, true);
            }
        }
    }
}
