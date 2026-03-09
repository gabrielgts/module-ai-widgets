<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Setup\Patch\Data;

use Gtstudio\AiAgents\Api\GetAiAgentByCodeInterface;
use Gtstudio\AiAgents\Api\SaveAiAgentInterface;
use Gtstudio\AiAgents\Model\Data\AiAgentData;
use Gtstudio\AiAgents\Model\Data\AiAgentDataFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreatePageBuilderAgent implements DataPatchInterface
{
    private const AGENT_CODE = 'page_builder';

    /**
     * @param GetAiAgentByCodeInterface $getAiAgentByCode
     * @param SaveAiAgentInterface $saveAiAgent
     * @param AiAgentDataFactory $agentFactory
     */
    public function __construct(
        private readonly GetAiAgentByCodeInterface $getAiAgentByCode,
        private readonly SaveAiAgentInterface $saveAiAgent,
        private readonly AiAgentDataFactory $agentFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        if ($this->agentExists()) {
            return $this;
        }

        /** @var AiAgentData $agent */
        $agent = $this->agentFactory->create();
        $agent->setCode(self::AGENT_CODE);
        $agent->setDescription('Generates clean semantic HTML content for Magento PageBuilder.');
        $agent->setBackground('You are a Magento PageBuilder content generator.');
        $agent->setOutput(
            "Return clean, optimized HTML.\n" .
            "Do not use markdown.\n" .
            "Use semantic HTML only."
        );

        $this->saveAiAgent->execute($agent);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Check whether the agent already exists in the database.
     *
     * @return bool
     */
    private function agentExists(): bool
    {
        try {
            $this->getAiAgentByCode->execute(self::AGENT_CODE);
            return true;
        } catch (NoSuchEntityException) {
            return false;
        }
    }
}
