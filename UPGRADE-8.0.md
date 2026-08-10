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
