<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Model\Service;

use Gtstudio\AiAgents\Api\AgentRunInterface;

class AdminChatService
{
    private const AGENT_CODE = 'admin_navigation';

    public function __construct(
        private readonly AgentRunInterface $agentRunner
    ) {
    }

    /**
     * Send a message to the admin navigation agent, optionally with conversation history as context.
     *
     * @param string $message
     * @param array  $history Array of {role: 'user'|'assistant', content: string}
     * @return array{content: string, tokens: int, model: string, provider: string}
     */
    public function ask(string $message, array $history = []): array
    {
        return $this->agentRunner->run(
            self::AGENT_CODE,
            $this->buildContextualMessage($message, $history)
        );
    }

    /**
     * Embed prior conversation turns into the message so the agent has context.
     */
    private function buildContextualMessage(string $message, array $history): string
    {
        if (empty($history)) {
            return $message;
        }

        $lines = ['Previous conversation:'];

        foreach ($history as $turn) {
            $role    = isset($turn['role']) && $turn['role'] === 'assistant' ? 'Assistant' : 'User';
            $content = isset($turn['content']) ? trim((string) $turn['content']) : '';

            if ($content !== '') {
                $lines[] = $role . ': ' . $content;
            }
        }

        $lines[] = '';
        $lines[] = 'Current message: ' . $message;

        return implode("\n", $lines);
    }
}
