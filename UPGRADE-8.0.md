# Backwards compatibility breaking changes

## Platform requirements

- The minimum required PHP version has been increased from **8.2** to **8.4**.

## Removed code

- Deprecated method `Mautic\LeadBundle\Model\LeadModel::isContactable()` removed. Use `Mautic\LeadBundle\Model\DoNotContact::isContactable()` instead.
<<<<<<< HEAD
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
=======
- Deprecated constant `Mautic\CampaignBundle\CampaignEvents::ON_EVENT_EXECUTION` (`mautic.campaign_on_event_execution`) removed. Listen to `CampaignEvents::ON_EVENT_EXECUTED` and `CampaignEvents::ON_EVENT_FAILED` instead.
- Deprecated constant `Mautic\CampaignBundle\CampaignEvents::ON_EVENT_DECISION_TRIGGER` (`mautic.campaign_on_event_decision_trigger`) removed. Listen to `CampaignEvents::ON_EVENT_DECISION_EVALUATION` instead.
- Deprecated class `Mautic\CampaignBundle\Event\CampaignDecisionEvent` removed. It was only dispatched with the removed `ON_EVENT_DECISION_TRIGGER` event. Use `Mautic\CampaignBundle\Event\DecisionEvent` instead.
- Methods `Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher::dispatchExecutionEvents()` and `::dispatchDecisionEvent()` removed, as they only dispatched the removed events.
- Methods `getLegacyEventsArray()` and `getLegacyEventsConfigArray()` removed from `Mautic\CampaignBundle\Event\EventArrayTrait`. They were only used to build the removed `CampaignDecisionEvent`.
- `Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher` no longer takes `LegacyEventDispatcher` as its second constructor argument.
>>>>>>> c6454b4066 ([removal] Remove deprecated CampaignEvents::ON_EVENT_EXECUTION and ON_EVENT_DECISION_TRIGGER)
