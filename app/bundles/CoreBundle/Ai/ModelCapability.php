<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

enum ModelCapability: string
{
    case Chat = 'chat';
    case StructuredOutput = 'structured-output';
    case Embeddings = 'embeddings';
    case Streaming = 'streaming';
    case ToolCalling = 'tool-calling';
}
