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
- `Mautic\DashboardBundle\Event\WidgetDetailEvent`: the legacy filesystem widget cache is gone. `setCacheDir()` was removed, the `$cacheProvider` constructor argument is now required, and `setTemplateData()` lost its 2nd `$skipCache` parameter. Widget data is cached only through `Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface`.
