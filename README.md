# Mautic Integrations

> Integrations solutions structured to mirror current Integrations and created as transition to final product.

## Install integrations bundle

Bundle is to be installed as any other common plugin even it is to be a part of Mautic in the future.

Create app/bundles/PluginBundle/Integration/UnifiedIntegrationInterface.php

```php
<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Integration;

/**
 * Interface UnifiedIntegrationInterface is used for type hinting.
 */
interface UnifiedIntegrationInterface
{
}
```
### Composer requirements and dependencies

