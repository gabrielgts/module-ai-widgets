<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Model\Service;

use Gtstudio\AiAgents\Api\AgentRunInterface;

class PageBuilderGenerator
{
    private const AGENT_CODE = 'page_builder';

    /**
     * @param AgentRunInterface $agentRunner
     */
    public function __construct(
        private readonly AgentRunInterface $agentRunner
    ) {
    }

    /**
     * Generate PageBuilder HTML content via the registered agent.
     *
     * @param string $prompt
     * @return string
     * @throws \Throwable
     */
    public function generate(string $prompt): string
    {
        return $this->agentRunner->run(self::AGENT_CODE, $prompt);
    }
}
