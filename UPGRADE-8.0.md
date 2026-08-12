# Backwards compatibility breaking changes

## Platform requirements

- The minimum required PHP version has been increased from **8.2** to **8.4**.

## Removed code

- Deprecated method `Mautic\LeadBundle\Model\LeadModel::isContactable()` removed. Use `Mautic\LeadBundle\Model\DoNotContact::isContactable()` instead.
- Deprecated method `Mautic\CampaignBundle\EventCollector\Builder\ConnectionBuilder::addDeprecatedAnchorRestrictions()` removed. With it, the campaign event keys `associatedActions`, `associatedDecisions` and `anchorRestrictions` are no longer read. Use the `connectionRestrictions` key instead:

```diff
 $event = [
-    'associatedDecisions' => ['decision1'],
-    'anchorRestrictions'  => ['decision2.top'],
+    'connectionRestrictions' => [
+        'source' => ['decision' => ['decision1']],
+        'anchor' => ['decision2.top'],
+    ],
 ];
```

  On a decision, `associatedActions` maps to `connectionRestrictions['target']['action']`; on an action, `associatedDecisions` maps to `connectionRestrictions['source']['decision']`.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\HtmlToUnicodeEmojiMap` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\ShortToUnicodeEmojiMap` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\UnicodeToHtmlEmojiMap` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\EmojiMap\UnicodeToShortEmojiMap` removed with no replacement.
- Class `Mautic\CoreBundle\Helper\EmojiHelper` removed with no replacement. All emoji conversion calls were dropped; emoji are stored and rendered as UTF-8 (`utf8mb4`) directly.
- Emoji sprite stylesheet `app/bundles/CoreBundle/Assets/css/libraries/emoji/` (`_emoji.scss` + `emoji.png`) removed together with its `@import` in `_libraries.scss`. It styled the `span.emoji-sizer`/`.emoji-outer`/`.emoji-inner` markup that `EmojiHelper::toHtml()` used to emit, which is no longer produced. Custom themes relying on those classes must ship their own CSS.
- Deprecated constant `Mautic\CampaignBundle\CampaignEvents::ON_EVENT_EXECUTION` (`mautic.campaign_on_event_execution`) removed. Listen to `CampaignEvents::ON_EVENT_EXECUTED` and `CampaignEvents::ON_EVENT_FAILED` instead.
- Deprecated constant `Mautic\CampaignBundle\CampaignEvents::ON_EVENT_DECISION_TRIGGER` (`mautic.campaign_on_event_decision_trigger`) removed. Listen to `CampaignEvents::ON_EVENT_DECISION_EVALUATION` instead.
- Deprecated class `Mautic\CampaignBundle\Event\CampaignDecisionEvent` removed. It was only dispatched with the removed `ON_EVENT_DECISION_TRIGGER` event. Use `Mautic\CampaignBundle\Event\DecisionEvent` instead.
- Methods `Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher::dispatchExecutionEvents()` and `::dispatchDecisionEvent()` removed, as they only dispatched the removed events.
- Methods `getLegacyEventsArray()` and `getLegacyEventsConfigArray()` removed from `Mautic\CampaignBundle\Event\EventArrayTrait`. They were only used to build the removed `CampaignDecisionEvent`.
- `Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher` no longer takes `LegacyEventDispatcher` as its second constructor argument.
- Deprecated class `Mautic\CoreBundle\Helper\CacheStorageHelper` removed together with the `mautic.helper.cache_storage` service. Use the CacheBundle instead: inject `Mautic\CacheBundle\Cache\CacheProviderInterface` and call `getSimpleCache()` for the PSR-16 API the helper mimicked.

```diff
-use Mautic\CoreBundle\Helper\CacheStorageHelper;
+use Mautic\CacheBundle\Cache\CacheProviderInterface;

-public function __construct(private CacheStorageHelper $cache)
+public function __construct(private CacheProviderInterface $cache)
 {
 }

 public function getPendingCount(int $id): ?int
 {
-    return $this->cache->get("email|{$id}|pending");
+    return $this->cache->getSimpleCache()->get("email|{$id}|pending");
 }
```

  Mind the behaviour differences:
  - A cache miss now returns `null` instead of `false`, so `false === $value` checks must become `null === $value`.
  - Cached data moves from the `cache_items` database table to the adapter configured by the `cache_adapter` parameter (filesystem by default), so it is dropped by a cache clear. The `cache_items` table and `Mautic\CoreBundle\Entity\Cache` entity are kept, but are no longer written to by Mautic itself.
- `Mautic\PluginBundle\Integration\AbstractIntegration::getCache()` returns `Psr\SimpleCache\CacheInterface` instead of `CacheStorageHelper`, and its 2nd constructor argument is now `Mautic\CacheBundle\Cache\CacheProviderInterface`. Keys stay namespaced per integration, so `$this->cache->set('leadFields', $fields)` in an integration keeps working unchanged.
- `Mautic\DashboardBundle\Event\WidgetDetailEvent`: the legacy filesystem widget cache is gone. `setCacheDir()` and `setCacheTimeout()` were removed, the `$cacheProvider` constructor argument is now required, and `setTemplateData()` lost its 2nd `$skipCache` parameter. Widget data is cached only through `Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface`, with the lifetime taken from `Widget::getCacheTimeout()`.
- `Mautic\DashboardBundle\Factory\WidgetDetailEventFactory` no longer takes `UserHelper`, `CoreParametersHelper` and `PathsHelper` constructor arguments.
- Deprecated class `Mautic\CoreBundle\Session\Storage\Handler\RedisSentinelSessionHandler` removed with no replacement. It was deprecated since Mautic 5.0, slated for removal in 6.0, and had no references left in the codebase.
- Deprecated constants `Mautic\LeadBundle\Segment\OperatorOptions::IN` and `::NOT_IN` removed. Use `OperatorOptions::INCLUDING_ANY` and `OperatorOptions::EXCLUDING_ANY` instead — they carry the identical values `in` and `!in`.
- Deprecated method `Mautic\CoreBundle\Entity\UuidTrait::isValidUuid()` removed. Use `Mautic\CoreBundle\Helper\UuidHelper::isValidUuid()` instead — calling static trait methods directly is deprecated in PHP 8.4+.
- Deprecated service alias `mautic.config.model.sysinfo` removed. Use the FQCN service id `Mautic\ConfigBundle\Model\SysinfoModel` instead.
- Deprecated method `Mautic\NotificationBundle\Helper\NotificationHelper::unsubscribe()` removed with no replacement; it was unused. With it, the `LeadRepository` and `DoNotContact` constructor arguments of `NotificationHelper` were removed as well.
- Deprecated service id `symfony.filesystem` removed. `Symfony\Component\Filesystem\Filesystem` is now registered under its class name, so autowired and FQCN-based usages are unaffected; only container lookups by the `symfony.filesystem` string need updating.
- Deprecated action `Mautic\LeadBundle\Controller\AjaxController::addLeadUtmTagsAction()` (ajax action `lead:addLeadUtmTags`) removed with no replacement. Its only caller, the unused JavaScript function `Mautic.createLeadUtmTag()`, was removed as well.
- Deprecated interface `Mautic\CoreBundle\Entity\PublishStatusIconAttributesInterface` removed with no replacement. Nothing implemented it. Use the `CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE` event to change template params instead.
