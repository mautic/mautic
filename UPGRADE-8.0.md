# Backwards compatibility breaking changes

## Platform requirements

- The minimum required PHP version has been increased from **8.2** to **8.4**.

## Removed code

- Deprecated entity `Mautic\CoreBundle\Entity\Cache` removed with no replacement. It mapped the `cache_items` table but was never read or written anywhere in the codebase.
- Deprecated methods `getResult()` and `setResult()` removed from `Mautic\CampaignBundle\Event\ConditionEvent` and `Mautic\CampaignBundle\Event\DecisionEvent`. Campaign condition/decision listeners must type their event argument as `ConditionEvent` / `DecisionEvent` (not the deprecated parent `CampaignExecutionEvent`) and use the real API: conditions call `pass()` / `fail()` (read back with `wasConditionSatisfied()`), decisions call `setAsApplicable()` (read back with `wasDecisionApplicable()`). The broken `setChannel()` overrides on both events were also removed; they now inherit the working `CampaignExecutionEvent::setChannel()`. The executioners already read the applicability via `wasConditionSatisfied()` / `wasDecisionApplicable()`, so behaviour is unchanged.
- Deprecated class `Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher` removed. It dispatched the per-contact, deprecated `CampaignExecutionEvent` for campaign actions registered with the legacy `'eventName'`/`'callback'` config keys. `Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher` no longer takes it as a constructor argument and only dispatches the batch `Mautic\CampaignBundle\Event\PendingEvent` for actions declaring `'batchEventName'`. Campaign actions must register `'batchEventName'` and listen on a `PendingEvent` — the `'eventName'`/`'callback'` action path no longer runs.
- Deprecated trait `Mautic\CampaignBundle\Event\EventArrayTrait` removed. Its `getEventArray()` logic is now a private method inside `Mautic\CampaignBundle\Event\CampaignExecutionEvent`, the only remaining consumer.
- Deprecated constant `Mautic\DynamicContentBundle\DynamicContentEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.dwc.on_campaign_trigger_action`) removed, replaced by `ON_CAMPAIGN_BATCH_ACTION` (`mautic.dwc.on_campaign_batch_action`). The `dwc.push_content` action now runs on `Mautic\CampaignBundle\Event\PendingEvent`. Switch any registration or listener to `'batchEventName' => DynamicContentEvents::ON_CAMPAIGN_BATCH_ACTION` and iterate `getPending()`, calling `pass()`/`fail()` per log.
- Deprecated constant `Mautic\PageBundle\PageEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.page.on_campaign_trigger_action`) removed, replaced by `ON_CAMPAIGN_BATCH_ACTION` (`mautic.page.on_campaign_batch_action`). The `tracking.pixel.send` action now runs on `PendingEvent`. Switch to `'batchEventName' => PageEvents::ON_CAMPAIGN_BATCH_ACTION` and a `PendingEvent` listener.
- Deprecated constant `Mautic\PluginBundle\PluginEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.plugin.on_campaign_trigger_action`) removed, replaced by `ON_CAMPAIGN_BATCH_ACTION` (`mautic.plugin.on_campaign_batch_action`). The `plugin.leadpush` action now runs on `PendingEvent`. Switch to `'batchEventName' => PluginEvents::ON_CAMPAIGN_BATCH_ACTION` and a `PendingEvent` listener.
- Deprecated constant `MauticPlugin\MauticFocusBundle\FocusEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.focus.on_campaign_trigger_action`) removed, replaced by `ON_CAMPAIGN_BATCH_ACTION` (`mautic.focus.on_campaign_batch_action`). The `focus.show` action now runs on `PendingEvent`. Switch to `'batchEventName' => FocusEvents::ON_CAMPAIGN_BATCH_ACTION` and a `PendingEvent` listener.
- Deprecated constant `MauticPlugin\MauticSocialBundle\SocialEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.social.on_campaign_trigger_action`) removed, replaced by `ON_CAMPAIGN_BATCH_ACTION` (`mautic.social.on_campaign_batch_action`). The `twitter.tweet` action now runs on `PendingEvent`. Switch to `'batchEventName' => SocialEvents::ON_CAMPAIGN_BATCH_ACTION` and a `PendingEvent` listener. `MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper::sendTweetAction()` now takes the `Mautic\CampaignBundle\Entity\Event` entity instead of the legacy event array.
- Deprecated form type removed from `Mautic\FormBundle\Entity\Form`. Form types were no longer used, so the `$formType` property, the `getFormType()` method, the `form_type` ORM mapping and the `formType` API field are gone. The `form_type` database column is dropped by a migration. The `formType`/`form_type` key is no longer read from or written to the API, and is dropped from form export payloads.
- Deprecated Mautic v1 theme fallback removed from `Mautic\PageBundle\Controller\PublicController::indexAction()`. Public pages are now rendered solely from `Page::getCustomHtml()`; the legacy path that rendered `Page::getContent()` through a `@themes/<template>/html/page.html.twig` theme template (used when `customHtml` was empty) is gone. The unused `ThemeHelper` argument was dropped from `indexAction()`.
- Deprecated method `Mautic\LeadBundle\Model\LeadModel::isContactable()` removed. Use `Mautic\LeadBundle\Model\DoNotContact::isContactable()` instead.
- Redundant string service aliases removed; reference the service by its class name instead. Affected ids: `mautic.user.provider` (`Mautic\UserBundle\Security\Provider\UserProvider`), `mautic.security.authentication_handler` (`Mautic\UserBundle\Security\Authentication\AuthenticationHandler`), `mautic.security.saml.username_mapper` (`Mautic\UserBundle\Security\SAML\User\UserMapper`), `mautic.security.saml.entity_descriptor_provider` (`LightSaml\Builder\EntityDescriptor\SimpleEntityDescriptorBuilder`), `mautic.monolog.handler` (`Mautic\CoreBundle\Monolog\Handler\FileLogHandler`), `mautic.helper.paths` (`Mautic\CoreBundle\Helper\PathsHelper`), `mautic.helper.assetgeneration` (`Mautic\CoreBundle\Helper\AssetGenerationHelper`), `mautic.helper.token_builder` (`Mautic\CoreBundle\Helper\BuilderTokenHelper`), `mautic.helper.update_checks` (`Mautic\CoreBundle\Helper\PreUpdateCheckHelper`), `mautic.update.step_provider` (`Mautic\CoreBundle\Update\StepProvider`), `mautic.core.service.local_file_adapter` (`Mautic\CoreBundle\Service\LocalFileAdapterService`), `mautic.asset.upload.error.handler` (`Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler`), `mautic.cache.adapter.filesystem` / `mautic.cache.adapter.redis` / `mautic.cache.adapter.redis_tag_aware` (the matching `Mautic\CacheBundle\Cache\Adapter\*` classes), `mautic.sms.callback_handler_container` (`Mautic\SmsBundle\Callback\HandlerContainer`), the `mautic.integrations.helper*` ids (the matching `Mautic\IntegrationsBundle\Helper\*` classes), `mautic.integrations.sync.notification.handler_container` (`Mautic\IntegrationsBundle\Sync\Notification\Handler\HandlerContainer`), and the unused `mautic.lead.model.lead_segment_*` / `mautic.lead.model.relative_date` / `mautic.lead.model.random_parameter_name` ids (the matching `Mautic\LeadBundle\Segment\*` classes). Custom cache adapters configured via the `cache_adapter` / `cache_adapter_tag_aware` parameters must now use the adapter's class name as the service id.
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
- Deprecated service id `mautic.security` removed. `Mautic\CoreBundle\Security\Permissions\CorePermissions` is now registered under its class name only, so autowired and FQCN-based usages are unaffected; only container lookups by the `mautic.security` string need updating:

    ```diff
    -$security = $container->get('mautic.security');
    +$security = $container->get(Mautic\CoreBundle\Security\Permissions\CorePermissions::class);
    ```
- Deprecated action `Mautic\LeadBundle\Controller\AjaxController::addLeadUtmTagsAction()` (ajax action `lead:addLeadUtmTags`) removed with no replacement. Its only caller, the unused JavaScript function `Mautic.createLeadUtmTag()`, was removed as well.
- Deprecated interface `Mautic\CoreBundle\Entity\PublishStatusIconAttributesInterface` removed with no replacement. Nothing implemented it. Use the `CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE` event to change template params instead.
- Deprecated method `Mautic\CoreBundle\Helper\AbstractFormFieldHelper::setTranslator()` removed. The translator is autowired via the `#[Required] autowireFormFieldHelper()` method, so there is nothing to pass manually.
- Deprecated method `Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler::reschedule()` removed. Use `EventScheduler::rescheduleLogs()` instead, which takes an `ArrayCollection` of logs.
- Deprecated method `Mautic\CoreBundle\Model\VariantModelTrait::convertVariant()` removed. Use `Mautic\CoreBundle\Model\VariantConverterService` instead.
- Deprecated method `Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns::getForOriginalDateColumnAndUnit()` removed. Use `getGeneratedColumnForDateColumn()` instead — it takes the table name as its first argument and matches on it, while the removed method ignored the table and returned the last matching column of any table:

```diff
-$generatedColumn = $generatedColumns->getForOriginalDateColumnAndUnit('date_added', 'd');
+$generatedColumn = $generatedColumns->getGeneratedColumnForDateColumn('page_hits', 'date_added', 'd');
```

  `Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumnsInterface` declares `getGeneratedColumnForDateColumn()` in place of the removed method.
- Support for the legacy `mautic:disable-tracking` HTML attribute in email and page content is removed. Use `data-mautic-disable-tracking="true"` instead:

```diff
-<a href="https://mautic.org" mautic:disable-tracking>Do not track me</a>
+<a href="https://mautic.org" data-mautic-disable-tracking="true">Do not track me</a>
```
- Deprecated constant `Mautic\StageBundle\StageEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.stage.on_campaign_trigger_action`) removed. The `stage.change` campaign action runs through `StageEvents::ON_CAMPAIGN_BATCH_ACTION`. Listen to that event instead.
- Deprecated constant `Mautic\WebhookBundle\WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.webhook.campaign_on_trigger_action`) removed, replaced by `WebhookEvents::ON_CAMPAIGN_BATCH_ACTION` (`mautic.webhook.on_campaign_batch_action`). The `campaign.sendwebhook` action now runs on the batch `Mautic\CampaignBundle\Event\PendingEvent` instead of the per-contact, also-deprecated `CampaignExecutionEvent`. Anything registering the action with `'eventName' => WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION`, or listening on that event, must switch to `'batchEventName' => WebhookEvents::ON_CAMPAIGN_BATCH_ACTION` and a `PendingEvent` listener — iterate `getPending()` and call `pass()`/`fail()` per log.
- Deprecated method `Mautic\CampaignBundle\Entity\CampaignRepository::fetchEmailIdsById()` removed. Use `Mautic\CampaignBundle\Entity\EventRepository::getCampaignEmailEvents()` instead — mind that it returns campaign event rows, not a flat list of email ids.
- Deprecated method `Mautic\LeadBundle\Field\Dispatcher\FieldDeleteDispatcher::dispatchPreDeleteEvent()` removed. Call `dispatchEvent(LeadEvents::FIELD_PRE_DELETE, $entity)` instead. With it, `FieldDeleteDispatcher` no longer takes `Mautic\LeadBundle\Field\BackgroundSettings` as a constructor argument; the background-processing guard already lives in `LeadFieldDeleter` and `FieldColumnDispatcher`.
- Deprecated constant `Mautic\EmailBundle\EmailEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.email.on_campaign_trigger_action`) removed. Nothing dispatched it any more — both `email.send` campaign actions run through `EmailEvents::ON_CAMPAIGN_BATCH_ACTION`. Listen to that event instead.
- Deprecated methods `Mautic\CoreBundle\Event\BuilderEvent::addTokensFromHelper()` and `::getTokensFromHelper()` removed. Build the tokens with `Mautic\CoreBundle\Helper\BuilderTokenHelper` yourself and pass them to `BuilderEvent::addTokens()`.
- `Mautic\LeadBundle\Entity\LeadListRepository` no longer uses `OperatorListTrait`, so `getFilterExpressionFunctions()`, `getOperatorsForFieldType()` and `getOperatorChoiceList()` are no longer available on the repository. Use `Mautic\LeadBundle\Provider\TypeOperatorProvider` instead. The trait itself is unchanged.
- Deprecated method `Mautic\CampaignBundle\Entity\CampaignRepository::getCampaignLeadCount()` removed with no replacement. It had no callers left.
- Deprecated constant `Mautic\CoreBundle\Command\ModeratedCommand::MODE_LOCK` (`file_lock`) removed, together with the branch that silently rewrote that mode to `flock`. `--lock_mode=file_lock` now fails with `Unknown locking method specified.` — use `--lock_mode=flock`. The `pid`, `flock` and `redis` modes are unchanged.
- Deprecated method `Mautic\CoreBundle\Controller\CommonController::accessDenied()` removed. Use `throwAccessDenied()` (throws `AccessDeniedHttpException`) or `getAccessDeniedFlash()` instead. Note the removed method always threw as well — its `array` return value was unreachable — so `Mautic\LeadBundle\Controller\NoteController::newAction()` and `::editAction()` lost `array` from their return types.
- Deprecated GrapesJS asset endpoint removed: route `grapesjsbuilder_assets` (`/grapesjsbuilder/assets`), `MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::assetsAction()` and `MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager::getImages()`. Use `grapesjsbuilder_media` (`/grapesjsbuilder/media`), `getMediaAction()` and `getMediaFiles()` instead — the builder JS has been requesting the paginated media endpoint since 5.2. With it, the `data-assets` attribute of the `#grapesjsbuilder_assets` element and the `dataAssets` template parameter are gone.
- Deprecated parameters `$removeEmpty` and `$deprecatedIgnoreNumerical` removed from `Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList()`, which now takes the list only. `$removeEmpty` was never read; pass the list to `parseBooleanList()` directly in place of `$deprecatedIgnoreNumerical = true`:

```diff
-$choices = FormFieldHelper::parseList($list, true, true);
+$choices = FormFieldHelper::parseBooleanList($list);
```

- Deprecated route `mautic_receive_sms` (`/sms/receive`) removed. It was a Twilio-specific alias that forwarded to `ReplyController::callbackAction()` with `transport` hardcoded to `twilio`. Point the callback URL configured in Twilio at `/sms/twilio/callback` (route `mautic_sms_callback`) instead.
- Deprecated parameter `$formType` removed from `Mautic\FormBundle\Entity\FormRepository::getFormList()`, together with the `f.formType` filter it applied. With it, the `form_type` option of `Mautic\FormBundle\Form\Type\FormListType` is gone — nothing passed it, so the returned form list is unchanged.
- Deprecated methods `Mautic\CampaignBundle\Entity\Campaign::getOnclickMethod()`, `::getDataAttributes()` and `::getTranslationKeysDataAttributes()` removed. They returned hardcoded publish-status confirmation attributes for the campaign list template, which now passes those values inline. Use the `CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE` event to change template params instead.
- `Mautic\LeadBundle\Entity\LeadRepository` no longer uses `OperatorListTrait`, so `getFilterExpressionFunctions()`, `getOperatorsForFieldType()` and `getOperatorChoiceList()` are no longer available on it. Use `Mautic\LeadBundle\Provider\TypeOperatorProvider` instead. The trait itself is unchanged.
- Deprecated method `Mautic\LeadBundle\Model\FieldModel::getFieldList()` removed. Use `Mautic\LeadBundle\Field\FieldList::getFieldList()` instead — the removed method only proxied to it. `FieldModel` no longer takes `FieldList` as a constructor argument, and the classes that used the proxy take `FieldList` instead of (or next to) `FieldModel`: `Mautic\LeadBundle\Deduplicate\ContactDeduper`, `Mautic\LeadBundle\Deduplicate\CompanyDeduper` (both via `DeduperTrait`, whose `$fieldModel` property is now `$fieldList`), `Mautic\LeadBundle\Form\Type\LeadFieldsType`, `Mautic\LeadBundle\Services\ContactColumnsDictionary`, `Mautic\LeadBundle\Model\LeadModel` and `Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper`.
- Deprecated constructor argument `$operators` removed from `Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent`; the event now starts with an empty operator list. Subscribe to `LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE` and call `addOperator()` instead of passing operators in.
- Deprecated template parameter `public` removed from the legacy Mautic 1 page rendering in `Mautic\PageBundle\Controller\PublicController`. Themes still reading `{{ public }}` in `page.html.twig` must drop it.
- Deprecated public method `getFilterExpressionFunctions()` removed from `Mautic\LeadBundle\Entity\OperatorListTrait` (and its override in `Mautic\LeadBundle\Provider\TypeOperatorProvider`). The trait now resolves its operator list through a protected `getFilterOperators()` seam, so behavior is unchanged: `TypeOperatorProvider` still returns the event-driven `FilterOperatorProvider::getAllOperators()` set, while other consumers keep the static `OperatorOptions` set. Code that called `getFilterExpressionFunctions()` on a trait consumer must use `Mautic\LeadBundle\Provider\TypeOperatorProvider` (e.g. `getOperatorsForFieldType()`) or `Mautic\LeadBundle\Segment\OperatorOptions::getFilterExpressionFunctions()` directly.
- Deprecated method `Mautic\EmailBundle\Helper\MailHelper::validateEmail()` removed. Use `Mautic\EmailBundle\Helper\EmailValidator` instead. Its only caller, `MauticPlugin\MauticCrmBundle\Api\HubspotApi::createLead()`, now validates the address itself, as the API helpers are not built through the container and cannot be given the validator service.
- Deprecated parameters `$shortenUrl` and `$utmTags` removed from `Mautic\PageBundle\Model\RedirectModel::generateRedirectUrl()`, which now takes the redirect and the clickthrough only. Call the public `shortenUrl()` and `applyUtmTags()` methods on the returned URL instead:

```diff
-$url = $redirectModel->generateRedirectUrl($redirect, $clickthrough, true, $utmTags);
+$url = $redirectModel->applyUtmTags($redirectModel->generateRedirectUrl($redirect, $clickthrough), $utmTags);
+$url = $redirectModel->shortenUrl($url);
```

- Deprecated support for `:`-prefixed parameter keys removed from `Mautic\LeadBundle\Segment\Query\QueryBuilder::setParameter()`. Pass the key without the colon — `setParameter('foo', $bar)`, not `setParameter(':foo', $bar)`. The `:foo` placeholder in the query string itself is unchanged.
- Deprecated `models` key in bundle `Config/config.php` `services` removed. Services listed under it are no longer registered with the `mautic.model` tag; register models by their class name and let autowiring resolve them.
- Deprecated method `Mautic\FormBundle\Entity\Form::setFormType()` removed with no replacement. Form types are no longer used; the `form_type` column and its getter are unchanged.
- Deprecated method `Mautic\FormBundle\Entity\Form::isStandalone()` removed with no replacement. All forms can now be used in campaigns, so the standalone/campaign distinction no longer exists.
- Deprecated class `Mautic\CampaignBundle\Event\CampaignScheduledEvent` removed. Its only subclass `Mautic\CampaignBundle\Event\ScheduledEvent` absorbed all of its properties and getters, so the dispatched event keeps the same public API. Code type-hinting or `instanceof`-checking `CampaignScheduledEvent` must target `ScheduledEvent` instead.
- Deprecated constant `Mautic\LeadBundle\LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION` (`mautic.lead.on_campaign_trigger_action`) removed. The eight built-in lead campaign actions (change points, change segments, modify tags, add to company, change company score, change owner, update contact, update primary company) and the internal set-manipulator listener now run on the batch event `LeadEvents::ON_CAMPAIGN_BATCH_ACTION` with a `Mautic\CampaignBundle\Event\PendingEvent` instead of the per-contact, also-deprecated `CampaignExecutionEvent`. Plugins that registered an action with `'eventName' => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION`, or added a listener on that event, must switch to `'batchEventName' => LeadEvents::ON_CAMPAIGN_BATCH_ACTION` and a `PendingEvent` listener — iterate `getPending()` and call `pass()`/`fail()` per log:

```diff
-'eventName' => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
+'batchEventName' => LeadEvents::ON_CAMPAIGN_BATCH_ACTION,
```

```diff
-public function onAction(CampaignExecutionEvent $event): void
-{
-    $lead = $event->getLead();
-    // ...
-    $event->setResult(true);
-}
+public function onAction(PendingEvent $event): void
+{
+    foreach ($event->getPending() as $log) {
+        $lead = $log->getLead();
+        // ...
+        $event->pass($log);
+    }
+}
```

- The `mautic.<bundle>.model.<name>` service aliases for model classes were removed. Every model is now resolved through `Mautic\CoreBundle\Factory\ModelFactory::getModel()` (keyed by the model's static `getName()`), so these aliases were unused. Inject `ModelFactory` and call `getModel('lead.lead')`, or type-hint the concrete model class directly:

```diff
-$leadModel = $container->get('mautic.lead.model.lead');
+$leadModel = $modelFactory->getModel('lead.lead');
```

  Non-model services that happen to live under the same `mautic.<bundle>.model.*` namespace (e.g. `mautic.lead.model.dnc` was a model but `mautic.report.model.report_exporter` is a helper) are unaffected — only aliases pointing at `MauticModelInterface` models were removed.

## Changed code

- CampaignBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\CampaignBundle\CampaignEvents` string constants. Update any subscriber or listener that keys on one of the converted `CampaignEvents::*` constants (or the raw string name such as `mautic.campaign_on_build`) to key on the event class instead:
- IntegrationsBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\IntegrationsBundle\IntegrationEvents` string constants. Update any subscriber or listener that keys on one of the converted `IntegrationEvents::*` constants to key on the event class instead:
- CoreBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\CoreBundle\CoreEvents` string constants. Update any subscriber or listener that keys on a `CoreEvents::*` constant (or the raw string name such as `mautic.build_menu`) to key on the event class instead:

    ```diff
     public static function getSubscribedEvents(): array
     {
         return [
    -        CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
    +        CampaignBuilderEvent::class      => ['onCampaignBuild', 0],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, CampaignEvents::CAMPAIGN_ON_BUILD)` becomes `$dispatcher->dispatch($event)`. The `Mautic\CampaignBundle\CampaignEvents` constants are kept for backwards compatibility but are no longer used internally for these events.

    Full mapping of old event name to new event class (all in the `Mautic\CampaignBundle\Event` namespace):

    | Old event name | `CampaignEvents` constant | New event class |
    | --- | --- | --- |
    | `mautic.on_campaign_delete` | `CampaignEvents::ON_CAMPAIGN_DELETE` | `DeleteCampaign` |
    | `mautic.campaign_on_build` | `CampaignEvents::CAMPAIGN_ON_BUILD` | `CampaignBuilderEvent` |
    | `mautic.campaign_on_trigger` | `CampaignEvents::CAMPAIGN_ON_TRIGGER` | `CampaignTriggerEvent` |
    | `mautic.campaign_on_event_executed` | `CampaignEvents::ON_EVENT_EXECUTED` | `ExecutedEvent` |
    | `mautic.campaign_on_event_executed_batch` | `CampaignEvents::ON_EVENT_EXECUTED_BATCH` | `ExecutedBatchEvent` |
    | `mautic.campaign_on_event_failed` | `CampaignEvents::ON_EVENT_FAILED` | `FailedEvent` |
    | `mautic.campaign_on_event_scheduled` | `CampaignEvents::ON_EVENT_SCHEDULED` | `ScheduledEvent` |
    | `mautic.campaign_on_event_scheduled_batch` | `CampaignEvents::ON_EVENT_SCHEDULED_BATCH` | `ScheduledBatchEvent` |
    | `mautic.campaign_on_event_decision_evaluation_results` | `CampaignEvents::ON_EVENT_DECISION_EVALUATION_RESULTS` | `DecisionResultsEvent` |
    | `mautic.campaign_failure_notify` | `CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY` | `NotifyOfFailureEvent` |
    | `mautic.campaign_unpublish_notify` | `CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY` | `NotifyOfUnpublishEvent` |
    -        IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
    +        InternalObjectEvent::class => ['collectInternalObjects', 0],
    -        CoreEvents::BUILD_MENU => ['onBuildMenu', 9999],
    +        MenuEvent::class => ['onBuildMenu', 9999],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS)` becomes `$dispatcher->dispatch($event)`. The `Mautic\IntegrationsBundle\IntegrationEvents` constants are kept for backwards compatibility but are no longer used internally for the events below.

    Full mapping of the converted constants to their event class (all in the `Mautic\IntegrationsBundle\Event` namespace):

    | `IntegrationEvents` constant | New event class |
    | --- | --- |
    | `IntegrationEvents::INTEGRATION_POST_EXECUTE` | `SyncEvent` |
    | `IntegrationEvents::INTEGRATION_CONFIG_FORM_LOAD` | `FormLoadEvent` |
    | `IntegrationEvents::INTEGRATION_CONFIG_ON_GENERATE_AUTH_URL` | `ConfigAuthUrlEvent` |
    | `IntegrationEvents::INTEGRATION_API_KEYS_BEFORE_SAVE` | `KeysSaveEvent` |
    | `IntegrationEvents::INTEGRATION_KEYS_BEFORE_ENCRYPTION` | `KeysEncryptionEvent` |
    | `IntegrationEvents::INTEGRATION_KEYS_AFTER_DECRYPTION` | `KeysDecryptionEvent` |
    | `IntegrationEvents::INTEGRATION_MAUTIC_SYNC_FIELDS_LOAD` | `MauticSyncFieldsLoadEvent` |
    | `IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS` | `InternalObjectEvent` |
    | `IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS` | `InternalObjectCreateEvent` |
    | `IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS` | `InternalObjectUpdateEvent` |
    | `IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD` | `InternalObjectFindByIdEvent` |
    | `IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE` | `InternalObjectRouteEvent` |
    | `IntegrationEvents::INTEGRATION_OBJECT_TOKEN_EVENT` | `MappedIntegrationObjectTokenEvent` |
    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, CoreEvents::BUILD_MENU)` becomes `$dispatcher->dispatch($event)`. The `Mautic\CoreBundle\CoreEvents` constants are kept for backwards compatibility but are no longer used internally. Note that listeners for the icon event (`Mautic\CoreBundle\Event\IconEvent`) must now be keyed on `IconEvent::class`, as that event was already dispatched by object.

    Full mapping of old event name to new event class (all in the `Mautic\CoreBundle\Event` namespace):

    | Old event name | `CoreEvents` constant | New event class |
    | --- | --- | --- |
    | `mautic.build_menu` | `CoreEvents::BUILD_MENU` | `MenuEvent` |
    | `mautic.build_route` | `CoreEvents::BUILD_ROUTE` | `RouteEvent` |
    | `mautic.global_search` | `CoreEvents::GLOBAL_SEARCH` | `GlobalSearchEvent` |
    | `mautic.list_stats` | `CoreEvents::LIST_STATS` | `StatsEvent` |
    | `mautic.build_command_list` | `CoreEvents::BUILD_COMMAND_LIST` | `CommandListEvent` |
    | `mautic.on_fetch_icons` | `CoreEvents::FETCH_ICONS` | `IconEvent` |
    | `mautic.build_embeddable_js` | `CoreEvents::BUILD_MAUTIC_JS` | `BuildJsEvent` |
    | `mautic.maintenance_cleanup_data` | `CoreEvents::MAINTENANCE_CLEANUP_DATA` | `MaintenanceEvent` |
    | `mautic.view_inject_custom_buttons` | `CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS` | `CustomButtonEvent` |
    | `mautic.view_inject_custom_content` | `CoreEvents::VIEW_INJECT_CUSTOM_CONTENT` | `CustomContentEvent` |
    | `mautic.view_inject_custom_template` | `CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE` | `CustomTemplateEvent` |
    | `mautic.view_inject_custom_assets` | `CoreEvents::VIEW_INJECT_CUSTOM_ASSETS` | `CustomAssetsEvent` |
    | `mautic.on_generated_columns_build` | `CoreEvents::ON_GENERATED_COLUMNS_BUILD` | `GeneratedColumnsEvent` |

- PluginBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\PluginBundle\PluginEvents` string constants. Update any subscriber or listener that keys on one of the converted `PluginEvents::*` constants to key on the event class instead:

    ```diff
     public static function getSubscribedEvents(): array
     {
         return [
    -        PluginEvents::ON_PLUGIN_INSTALL => ['onInstall', 0],
    +        PluginInstallEvent::class => ['onInstall', 0],
         ];
     }
    ```

- AssetBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\AssetBundle\AssetEvents` string constants. This covers `ASSET_ON_LOAD` (`AssetLoadEvent`), `ASSET_ON_REMOTE_BROWSE` (`RemoteAssetBrowseEvent`) and the CRUD group `ASSET_PRE_SAVE` (`AssetPreSaveEvent`), `ASSET_POST_SAVE` (`AssetPostSaveEvent`), `ASSET_PRE_DELETE` (`AssetPreDeleteEvent`), `ASSET_POST_DELETE` (`AssetPostDeleteEvent`). The CRUD group previously reused one `AssetEvent` object under four names; each action now dispatches its own `AssetEvent` subclass, so `AssetEvent` is no longer `final`. The dead `ASSET_ON_UPLOAD` constant (never dispatched or listened to) has been removed. Update any subscriber or listener that keys on a converted constant to key on the event class instead:

    ```diff
     public static function getSubscribedEvents(): array
     {
         return [
    -        AssetEvents::ASSET_ON_LOAD => ['onAssetDownload', 0],
    +        AssetLoadEvent::class => ['onAssetDownload', 0],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, PluginEvents::ON_PLUGIN_INSTALL)` becomes `$dispatcher->dispatch($event)`. The `Mautic\PluginBundle\PluginEvents` constants are kept for backwards compatibility but are no longer used internally for the events below. Constants that share an event class (e.g. the `PLUGIN_ON_INTEGRATION_KEYS_ENCRYPT` / `_KEYS_DECRYPT` / `_KEYS_MERGE` group and the `PLUGIN_ON_INTEGRATION_REQUEST` / `_RESPONSE` pair) are unchanged.

    Full mapping of the converted constants to their event class (all in the `Mautic\PluginBundle\Event` namespace):

    | `PluginEvents` constant | New event class |
    | --- | --- |
    | `PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE` | `PluginIntegrationEvent` |
    | `PluginEvents::PLUGIN_ON_INTEGRATION_AUTH_REDIRECT` | `PluginIntegrationAuthRedirectEvent` |
    | `PluginEvents::PLUGIN_ON_INTEGRATION_GET_AUTH_CALLBACK_URL` | `PluginIntegrationAuthCallbackUrlEvent` |
    | `PluginEvents::PLUGIN_ON_INTEGRATION_FORM_DISPLAY` | `PluginIntegrationFormDisplayEvent` |
    | `PluginEvents::PLUGIN_ON_INTEGRATION_FORM_BUILD` | `PluginIntegrationFormBuildEvent` |
    | `PluginEvents::ON_PLUGIN_UPDATE` | `PluginUpdateEvent` |
    | `PluginEvents::ON_PLUGIN_INSTALL` | `PluginInstallEvent` |
    | `PluginEvents::PLUGIN_IS_PUBLISHED_STATE_CHANGING` | `PluginIsPublishedEvent` |

- ChannelBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\ChannelBundle\ChannelEvents` string constants. Update any subscriber or listener that keys on one of the converted `ChannelEvents::*` constants to key on the event class instead:

    ```diff
     public static function getSubscribedEvents(): array
     {
         return [
    -        ChannelEvents::ADD_CHANNEL => ['onAddChannel', 0],
    +        ChannelEvent::class => ['onAddChannel', 0],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, ChannelEvents::CHANNEL_BROADCAST)` becomes `$dispatcher->dispatch($event)`. The `Mautic\ChannelBundle\ChannelEvents` constants are kept for backwards compatibility but are no longer used internally for the events below. Constants that share an event class are unchanged: the `MESSAGE_PRE_SAVE` / `MESSAGE_POST_SAVE` / `MESSAGE_PRE_DELETE` / `MESSAGE_POST_DELETE` group on `MessageEvent`, and `ON_CAMPAIGN_BATCH_ACTION` on the shared `Mautic\CampaignBundle\Event\PendingEvent`.

    Full mapping of the converted constants to their event class (all in the `Mautic\ChannelBundle\Event` namespace):

    | `ChannelEvents` constant | New event class |
    | --- | --- |
    | `ChannelEvents::ADD_CHANNEL` | `ChannelEvent` |
    | `ChannelEvents::CHANNEL_BROADCAST` | `ChannelBroadcastEvent` |
    | `ChannelEvents::MESSAGE_QUEUED` | `MessageQueueEvent` |
    | `ChannelEvents::PROCESS_MESSAGE_QUEUE` | `MessageQueueProcessEvent` |
    | `ChannelEvents::PROCESS_MESSAGE_QUEUE_BATCH` | `MessageQueueBatchProcessEvent` |
- DynamicContentBundle's `ON_CONTACTS_FILTER_EVALUATE` event is now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\DynamicContentBundle\DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE` string constant. Update any subscriber or listener that keys on that constant to key on `Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent::class` instead, e.g. `$dispatcher->dispatch($event, DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE)` becomes `$dispatcher->dispatch($event)`. The constant is kept for backwards compatibility but is no longer used internally. The `DynamicContentEvent` CRUD group (`PRE_SAVE` / `POST_SAVE` / `PRE_DELETE` / `POST_DELETE`) shares one event class under four names and is unchanged, as are the cross-bundle `CategoryEvent`, `TokenReplacementEvent` and campaign event constants.
- WebhookBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\WebhookBundle\WebhookEvents` string constants. Update any subscriber or listener that keys on one of the converted `WebhookEvents::*` constants to key on the event class instead:
- PageBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\PageBundle\PageEvents` string constants. Update any subscriber or listener that keys on one of the converted `PageEvents::*` constants (or the raw string name such as `mautic.page_on_hit`) to key on the event class instead:

    ```diff
     public static function getSubscribedEvents(): array
     {
         return [
    -        WebhookEvents::WEBHOOK_ON_BUILD => ['onWebhookBuild', 0],
    +        WebhookBuilderEvent::class => ['onWebhookBuild', 0],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, WebhookEvents::WEBHOOK_ON_BUILD)` becomes `$dispatcher->dispatch($event)`. The `Mautic\WebhookBundle\WebhookEvents` constants are kept for backwards compatibility but are no longer used internally for the events below.

    | `WebhookEvents` constant | New event class (in `Mautic\WebhookBundle\Event`) |
    | --- | --- |
    | `WebhookEvents::WEBHOOK_ON_BUILD` | `WebhookBuilderEvent` |
    | `WebhookEvents::WEBHOOK_QUEUE_ON_ADD` | `WebhookQueueEvent` |
    | `WebhookEvents::WEBHOOK_ON_REQUEST` | `WebhookRequestEvent` |

    The CRUD constants (`WEBHOOK_PRE_SAVE` / `WEBHOOK_POST_SAVE` / `WEBHOOK_PRE_DELETE` / `WEBHOOK_POST_DELETE`) and `WEBHOOK_KILL` all share the `WebhookEvent` class, so they keep their string names and are unchanged.
    -        PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
    +        PageDisplayEvent::class => ['onPageDisplay', 0],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY)` becomes `$dispatcher->dispatch($event)`. The `Mautic\PageBundle\PageEvents` constants are kept for backwards compatibility but are no longer used internally for the events below. Constants that share an event class stay as string constants: the `PageEvent` group (`PAGE_ON_BUILD`, `PAGE_PRE_SAVE`, `PAGE_POST_SAVE`, `PAGE_PRE_DELETE`, `PAGE_POST_DELETE`, `PAGE_ON_TOGGLE_PUBLISH`) and the `DetermineWinnerEvent` pair (`ON_DETERMINE_BOUNCE_RATE_WINNER`, `ON_DETERMINE_DWELL_TIME_WINNER`) are unchanged, as are the campaign constants (`ON_CAMPAIGN_TRIGGER_DECISION`, `ON_CAMPAIGN_BATCH_ACTION`) whose events are shared across bundles.

    Full mapping of the converted constants to their event class (all in the `Mautic\PageBundle\Event` namespace):

    | `PageEvents` constant | New event class |
    | --- | --- |
    | `PageEvents::VIDEO_ON_HIT` | `VideoHitEvent` |
    | `PageEvents::PAGE_ON_HIT` | `PageHitEvent` |
    | `PageEvents::PAGE_ON_DISPLAY` | `PageDisplayEvent` |
    | `PageEvents::REDIRECT_DO_NOT_TRACK` | `UntrackableUrlsEvent` |
    | `PageEvents::ON_REDIRECT_GENERATE` | `RedirectGenerationEvent` |
    | `PageEvents::ON_CONTACT_TRACKED` | `TrackingEvent` |
- SmsBundle events are now dispatched by the event object alone, so the event name is the event class (Symfony 4.3+) instead of the `Mautic\SmsBundle\SmsEvents` string constants. Update any subscriber or listener that keys on one of the converted `SmsEvents::*` constants to key on the event class instead:

    ```diff
     public static function getSubscribedEvents(): array
     {
         return [
    -        SmsEvents::DNC_FILTER_CONTACTS_ON_SEND => ['dncFilter', 0],
    +        DncEvent::class => ['dncFilter', 0],
         ];
     }
    ```

    Dispatching drops the redundant second argument, e.g. `$dispatcher->dispatch($event, SmsEvents::SMS_ON_SEND)` becomes `$dispatcher->dispatch($event)`. The `Mautic\SmsBundle\SmsEvents` constants are kept for backwards compatibility but are no longer used internally for the events below. Constants that share an event class stay unchanged: the `SMS_PRE_SAVE` / `SMS_POST_SAVE` / `SMS_PRE_DELETE` / `SMS_POST_DELETE` group (`SmsEvent`) and the `ON_REPLY` / `ON_CAMPAIGN_REPLY` pair (`ReplyEvent`). The webhook-type identifiers `TOKEN_REPLACEMENT` (shared `CoreBundle` event) and the campaign trigger constants also stay as strings.

    Full mapping of the converted constants to their event class (all in the `Mautic\SmsBundle\Event` namespace):

    | `SmsEvents` constant | New event class |
    | --- | --- |
    | `SmsEvents::SMS_ON_SEND` | `SmsSendEvent` |
    | `SmsEvents::ON_SMS_TOKENS_BUILD` | `TokensBuildEvent` |
    | `SmsEvents::DNC_FILTER_CONTACTS_ON_SEND` | `DncEvent` |
    | `SmsEvents::QUEUE_FILTER_CONTACTS_ON_SEND` | `QueueEvent` |
    | `SmsEvents::FILTER_CONTACTS_ON_SEND` | `FilterEvent` |

- `Mautic\CoreBundle\Factory\ModelFactory` now builds its service locator from a `defaultIndexMethod` on the `mautic.model` tag, replacing the removed `Mautic\CoreBundle\DependencyInjection\Compiler\ModelPass`. Every model (a service implementing `Mautic\CoreBundle\Model\MauticModelInterface`) declares its `ModelFactory::getModel()` lookup key via a static `getName()` method:

    ```php
    public static function getName(): string
    {
        return 'lead.lead';
    }
    ```

    A custom model without `getName()` is still registered in the locator, but only under its fully-qualified class name; add the method so `$modelFactory->getModel('yourbundle.yourmodel')` can resolve it. `ModelFactory::getModel()` now resolves keys solely through this method and no longer accepts a fully-qualified class name.

- `Mautic\CoreBundle\Security\Permissions\AbstractPermissions::definePermissions()` was removed. Define the permissions in the constructor instead:

    ```diff
    -public function definePermissions(): void
    +public function __construct()
     {
         $this->addStandardPermissions('categories');
     }
    ```

    Permission classes that already define their permissions in the constructor need no change.

- The resolved Mautic parameters are injected into `AbstractPermissions` by the autowired `setCoreParametersHelper()` method, so `$this->params` is available in every method except the constructor. The `array $params` constructor argument is deprecated and defaults to an empty array; drop it from your permission class:

    ```diff
    -public function __construct(array $params)
    +public function __construct()
     {
    -    parent::__construct($params);
    -
         $this->addStandardPermissions('categories');
     }
    ```

- All permission classes are now registered as services and tagged with `mautic.permissions`, instead of being instantiated on the fly by `Mautic\CoreBundle\Security\Permissions\CorePermissions`. A permission class that is not registered as a service is still instantiated on the fly. Register it in the bundle's `Config/services.php` to have it managed by the container and to autowire other services into it; the `mautic.permissions` tag is added automatically to every `AbstractPermissions` child registered with autoconfiguration enabled:

    ```php
    $services->set(MauticPlugin\AcmeBundle\Security\Permissions\AcmePermissions::class);
    ```

- The query-builder and filter parameters of `Mautic\CoreBundle\Entity\CommonRepository` methods now carry native type declarations instead of docblock-only types: the query builder is `Doctrine\ORM\QueryBuilder|Doctrine\DBAL\Query\QueryBuilder` and the search filter is `\stdClass`. Repositories that extend `CommonRepository` and override these methods (most commonly `addCatchAllWhereClause()` and `addSearchCommandWhereClause()`) must keep their overrides compatible — either drop the parameter types entirely or match the parent exactly. Callers that pass a filter which is not a `\stdClass` will now hit a `TypeError`.

    ```diff
    -    protected function addCatchAllWhereClause($qb, $filter): array
    +    protected function addCatchAllWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $qb, \stdClass $filter): array

    -    protected function addSearchCommandWhereClause($q, $filter): array
    +    protected function addSearchCommandWhereClause(\Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $queryBuilder, \stdClass $filter): array
    ```

- `Mautic\CoreBundle\Entity\CommonRepository::getIdsExpr()` return type was narrowed from `mixed` to `Doctrine\ORM\Query\Expr\Func|string|false`. Overrides must return a compatible type.
- The `Mautic\CoreBundle\DependencyInjection\Compiler\ServicePass` compiler pass was removed. It used to read the `services > menus` array from a bundle's `Config/config.php` and wire the menu item (`knp_menu.menu`) and its renderer (`knp_menu.renderer`) automatically. A bundle that registered its own menu must now declare both services explicitly in its `Config/services.php`.

    Before — `Config/config.php`:

    ```php
    'services' => [
        'menus' => [
            'mautic.menu.mybundle' => [
                'alias'   => 'mybundle',
                'options' => ['template' => '@MyBundle/Menu/mybundle.html.twig'],
            ],
        ],
    ],
    ```

    After — `Config/services.php`:

    ```php
    use Knp\Menu\MenuItem;
    use Mautic\CoreBundle\Menu\MenuBuilder;
    use Mautic\CoreBundle\Menu\MenuRenderer;

    use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

    $services->set('mautic.menu.mybundle', MenuItem::class)
        ->factory([service(MenuBuilder::class), 'mybundleMenu'])
        ->tag('knp_menu.menu', ['alias' => 'mybundle']);

    $services->set('mautic.menu_renderer.mybundle', MenuRenderer::class)
        ->args([service('knp_menu.matcher'), service('twig'), ['template' => '@MyBundle/Menu/mybundle.html.twig']])
        ->tag('knp_menu.renderer', ['alias' => 'mybundle']);
    ```
