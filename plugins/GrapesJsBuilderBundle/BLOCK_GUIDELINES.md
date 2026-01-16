## Example: Independent Mautic plugin `CustomBlocksBundle` adding a new **MJML section block** (`t-section t-surface-2`)

Goal: ship a standalone Mautic plugin under `plugins/CustomBlocksBundle` that registers a GrapesJS plugin through `window.MauticGrapesJsPlugins` and adds a new block for the **email MJML** builder.

---

## 1) Recommended file structure

```text
plugins/CustomBlocksBundle/
├─ CustomBlocksBundle.php
├─ Config/
│  ├─ config.php
│  └─ services.php
├─ EventListener/
│  └─ GrapesJsInjectSubscriber.php
└─ Assets/
   └─ js/
      └─ grapesjs.customBlocks.js
```

---

## 2) JS: register into `window.MauticGrapesJsPlugins` and add blocks

### File: `plugins/CustomBlocksBundle/Assets/js/grapesjs.customBlocks.js`

```js
(function () {
  window.MauticGrapesJsPlugins = window.MauticGrapesJsPlugins || [];

  // Avoid double-registration if the asset is injected multiple times
  const alreadyRegistered = window.MauticGrapesJsPlugins.some(p => p && p.name === 'customblocks-mjml-blocks');
  if (alreadyRegistered) return;

  window.MauticGrapesJsPlugins.push({
    name: 'customblocks-mjml-blocks',
    context: ['email-mjml'],
    plugin: (editor, opts = {}) => {
      const bm = editor.BlockManager;

      // Block: Section (Surface 2)
      const sectionSurface2Id = 'customblocks-section-surface-2';
      if (!bm.get(sectionSurface2Id)) {
        bm.add(sectionSurface2Id, {
          label: 'Section (Surface 2)',
          category: 'Custom Blocks',
          content:
            '<mj-section mj-class="t-section t-surface-2">' +
              '<mj-column>' +
                '<mj-text mj-class="t-body">Section content...</mj-text>' +
              '</mj-column>' +
            '</mj-section>',
          media: `<svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M4 6h16v4H4V6zm0 8h16v4H4v-4z"/>
          </svg>`,
        });
      }

      // Block: Secondary Button
      const secondaryButtonId = 'customblocks-button-secondary';
      if (!bm.get(secondaryButtonId)) {
        bm.add(secondaryButtonId, {
          label: 'Secondary Button',
          category: 'Custom Blocks',
          content: '<mj-button mj-class="t-btn t-btn-secondary" href="https://">Secondary Button</mj-button>',
          media: `<svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M7 7h10a4 4 0 0 1 0 8H7a4 4 0 0 1 0-8Zm0 2a2 2 0 0 0 0 4h10a2 2 0 0 0 0-4H7Z"/>
          </svg>`,
        });
      }
    },
  });
})();
```

### Adding more blocks

To add additional blocks, follow this pattern inside the `plugin` function:

```js
const myBlockId = 'customblocks-my-block';
if (!bm.get(myBlockId)) {
  bm.add(myBlockId, {
    label: 'My Block Label',
    category: 'Custom Blocks',
    content: '<mj-... mj-class="t-...">...</mj-...>',
    media: `<svg viewBox="0 0 24 24">...</svg>`,
  });
}
```

---

## 3) PHP: inject the JS asset on builder pages

This is the piece that makes it an “independent plugin”: your bundle injects its JS into pages where the builder runs.

### File: `plugins/CustomBlocksBundle/EventListener/GrapesJsInjectSubscriber.php`

```php
<?php

declare(strict_types=1);

namespace MauticPlugin\CustomBlocksBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GrapesJsInjectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AssetsHelper $assetsHelper,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['onInjectCustomContent', 0],
        ];
    }

    public function onInjectCustomContent(CustomContentEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        // Keep this conservative: inject only on email builder-related routes/pages.
        // Adjust the conditions to match your instance routes.
        $route = (string) $request->attributes->get('_route');
        $path  = (string) $request->getPathInfo();

        $isEmailArea =
            str_contains($route, 'mautic_email') ||
            str_contains($path, '/emails');

        if (!$isEmailArea) {
            return;
        }

        $src = $this->assetsHelper->getUrl('plugins/CustomBlocksBundle/Assets/js/grapesjs.customBlocks.js');

        // Inject near the end of the body so `window.MauticGrapesJsPlugins` is available before builder init.
        $event->addContent(
            sprintf('<script src="%s"></script>', $src),
            CustomContentEvent::LOCATION_BODY_END
        );
    }
}
```

> Note: The exact “is builder page” detection varies by Mautic version/setup. If you can share the actual builder route(s) you want, tighten the condition to those specific routes.

---

## 4) Register the subscriber as a plugin event service

### File: `plugins/CustomBlocksBundle/Config/services.php`

```php
<?php

declare(strict_types=1);

return [
    'services' => [
        'events' => [
            'customblocks.grapesjs.inject.subscriber' => [
                'class'     => \MauticPlugin\CustomBlocksBundle\EventListener\GrapesJsInjectSubscriber::class,
                'arguments' => [
                    'mautic.helper.assets',
                    'request_stack',
                ],
            ],
        ],
    ],
];
```

---

## 5) Minimal bundle bootstrapping (if you need it)

### File: `plugins/CustomBlocksBundle/CustomBlocksBundle.php`

```php
<?php

declare(strict_types=1);

namespace MauticPlugin\CustomBlocksBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

class CustomBlocksBundle extends PluginBundleBase
{
}
```

### File: `plugins/CustomBlocksBundle/Config/config.php`

```php
<?php

declare(strict_types=1);

return [
    'name'        => 'CustomBlocks',
    'description' => 'Adds custom GrapesJS MJML blocks.',
    'version'     => '1.0.0',
    'author'      => 'Your Company',
];
```

---

## 6) Activating the plugin

After creating all files, run these commands to activate the plugin:

```bash
bin/console cache:clear
bin/console mautic:plugins:reload
```

> **Note:** You may need to hard-refresh your browser (Ctrl+Shift+R / Cmd+Shift+R) to clear cached JavaScript assets.

---

## Operating rule for AI agents (copy/paste)

When asked to add a new block as an independent plugin:

1. Add/extend a JS file under `plugins/<PluginName>Bundle/Assets/js/...` that pushes to `window.MauticGrapesJsPlugins`.
2. Scope with `context: ['email-mjml']` (or other).
3. Add blocks via `editor.BlockManager.add(...)` with MJML `content` using theme tokens (e.g. `mj-class="t-section t-surface-2"`).
4. Use `'Custom Blocks'` as the category for all custom blocks to group them together.
5. Always check if block exists before adding: `if (!bm.get(blockId)) { bm.add(...) }`.
6. Inject the JS on builder pages via `CoreEvents::VIEW_INJECT_CUSTOM_CONTENT` subscriber registered under `Config/services.php`.
7. All PHP files must include `declare(strict_types=1);` after the opening `<?php` tag.