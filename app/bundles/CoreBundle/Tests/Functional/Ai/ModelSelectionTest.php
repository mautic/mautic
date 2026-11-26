<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Ai;

use Mautic\CoreBundle\Ai\ConfiguredModel;
use Mautic\CoreBundle\Ai\ModelCapability;
use Mautic\CoreBundle\Ai\ModelSelection;
use Mautic\CoreBundle\Ai\ModelTier;
use Mautic\CoreBundle\Ai\OpenAiCompatibleProvider;
use Mautic\CoreBundle\Ai\ProviderLocation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class ModelSelectionTest extends TestCase
{
    public function testSelectsTheLowestEligibleLocalTierBeforeAnAllowedCloudModel(): void
    {
        $local   = new OpenAiCompatibleProvider(ProviderLocation::Local, 'http://ollama:11434/v1', new MockHttpClient());
        $cloud   = new OpenAiCompatibleProvider(ProviderLocation::Cloud, 'https://api.openai.com/v1', new MockHttpClient());
        $chat    = [ModelCapability::Chat->value => true];
        $small   = new ConfiguredModel($local, 'small', ModelTier::Economy, $chat, 2048);
        $regular = new ConfiguredModel($local, 'regular', ModelTier::Standard, $chat, 8192);
        $other   = new ConfiguredModel($local, 'other', ModelTier::Standard, $chat, 8192);
        $remote  = new ConfiguredModel($cloud, 'cloud', ModelTier::Economy, $chat, 32768);
        $select  = new ModelSelection([$remote, $regular, $other, $small]);

        $this->assertSame($small, $select->select([ModelCapability::Chat], ModelTier::Economy, allowCloud: true));
        $this->assertSame($regular, $select->select([ModelCapability::Chat], ModelTier::Economy, 4096, allowCloud: true));
        $this->assertSame($regular, $select->select([ModelCapability::Chat], ModelTier::Standard));
        $this->assertNull($select->select([ModelCapability::Chat], ModelTier::Economy, 16384));
        $this->assertSame($remote, $select->select([ModelCapability::Chat], ModelTier::Economy, 16384, allowCloud: true));
        $this->assertNull($select->select([ModelCapability::Chat], ModelTier::Advanced, allowCloud: true));
    }

    public function testUnknownOrMissingCapabilitiesLetTheFeatureChooseAReducedMode(): void
    {
        $local  = new OpenAiCompatibleProvider(ProviderLocation::Local, 'http://ollama:11434/v1', new MockHttpClient());
        $model  = new ConfiguredModel($local, 'small', ModelTier::Economy, [ModelCapability::Chat->value => true, ModelCapability::ToolCalling->value => false]);
        $select = new ModelSelection([$model]);

        $this->assertNull($model->supports(ModelCapability::Streaming));
        $this->assertFalse($model->supports(ModelCapability::ToolCalling));
        $this->assertNull($select->select([ModelCapability::Chat, ModelCapability::StructuredOutput], ModelTier::Economy));
        $this->assertNull($select->select([ModelCapability::Chat], ModelTier::Economy, 1));
        $this->assertSame($model, $select->select([ModelCapability::Chat], ModelTier::Economy));
    }
}
