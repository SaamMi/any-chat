<?php

namespace SaamMi\AnyChat\Services;

use SaamMi\AnyChat\Contracts\AiCopilot;

class NullAiCopilot implements AiCopilot
{
    public function generateReply(array $messages): ?string
    {
        return "AI Copilot is not configured. Please bind a custom implementation to the AiCopilot interface.";
    }
}