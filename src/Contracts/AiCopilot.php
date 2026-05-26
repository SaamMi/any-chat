<?php

namespace SaamMi\AnyChat\Contracts;

interface AiCopilot
{
    /**
     * Generate a reply based on the conversation history.
     *
     * @param array $messages An array of formatted message context.
     * @return string|null The suggested text reply.
     */
    public function generateReply(array $messages): ?string;
}