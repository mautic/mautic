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
