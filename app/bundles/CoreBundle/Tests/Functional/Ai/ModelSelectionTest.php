<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\ConfiguredModel;
use Mautic\CoreBundle\Ai\ModelCapability;
use Mautic\CoreBundle\Ai\ModelSelection;
use Mautic\CoreBundle\Ai\ModelWorkload;
use Mautic\CoreBundle\Ai\OpenAiCompatibleProvider;
use Mautic\CoreBundle\Ai\ProviderLocation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class ModelSelectionTest extends TestCase
{
    public function testUsesTheProviderAndModelAssignedToEachWorkload(): void
    {
        $local   = new OpenAiCompatibleProvider(ProviderLocation::Local, 'http://ollama:11434/v1', new MockHttpClient());
        $cloud   = new OpenAiCompatibleProvider(ProviderLocation::Cloud, 'https://api.openai.com/v1', new MockHttpClient());
        $chat    = [ModelCapability::Chat->value => true];
        $routine = new ConfiguredModel($local, 'routine-model', $chat, 2048);
        $complex = new ConfiguredModel($cloud, 'complex-model', $chat, 32768);
        $select  = new ModelSelection(routine: $routine, complex: $complex);

        $this->assertSame($routine, $select->select(ModelWorkload::Routine, [ModelCapability::Chat], allowCloud: true));
        $this->assertSame($complex, $select->select(ModelWorkload::Complex, [ModelCapability::Chat], allowCloud: true));
        $this->assertNull($select->select(ModelWorkload::Complex, [ModelCapability::Chat]));
        $this->assertNull($select->select(ModelWorkload::Routine, [ModelCapability::Chat], 4096, allowCloud: true));
    }

    public function testMissingRequirementsDoNotSwitchModels(): void
    {
        $local  = new OpenAiCompatibleProvider(ProviderLocation::Local, 'http://ollama:11434/v1', new MockHttpClient());
        $model  = new ConfiguredModel($local, 'local-model', [ModelCapability::Chat->value => true, ModelCapability::ToolCalling->value => false]);
        $select = new ModelSelection(complex: $model);

        $this->assertNull($model->supports(ModelCapability::Streaming));
        $this->assertFalse($model->supports(ModelCapability::ToolCalling));
        $this->assertNull($select->select(ModelWorkload::Complex, [ModelCapability::Chat, ModelCapability::StructuredOutput]));
        $this->assertNull($select->select(ModelWorkload::Complex, [ModelCapability::Chat], 1));
        $this->assertNull($select->select(ModelWorkload::Routine, [ModelCapability::Chat]));
        $this->assertSame($model, $select->select(ModelWorkload::Complex, [ModelCapability::Chat]));
        $this->assertSame($model, new ModelSelection($model, $model)->select(ModelWorkload::Routine, [ModelCapability::Chat]));
    }
}
