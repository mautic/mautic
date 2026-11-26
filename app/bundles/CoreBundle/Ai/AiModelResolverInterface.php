<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

/**
 * @internal
 */
interface AiModelResolverInterface
{
    public function resolve(ModelWorkload $workload): ConfiguredModel;
}
