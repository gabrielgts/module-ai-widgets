<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Setup\Patch\Data;

use Gtstudio\AiAgents\Api\GetAiAgentByCodeInterface;
use Gtstudio\AiAgents\Api\SaveAiAgentInterface;
use Gtstudio\AiAgents\Model\Data\AiAgentData;
use Gtstudio\AiAgents\Model\Data\AiAgentDataFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateAdminNavigationAgent implements DataPatchInterface
{
    private const AGENT_CODE = 'admin_navigation';

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
        $agent->setDescription('Floating admin assistant that helps users navigate the Magento admin panel.');
        $agent->setBackground(
            "You are a Magento admin navigation assistant.\n" .
            "Your sole purpose is to help admin users find features, settings, and pages within the Magento admin panel."
        );
        $agent->setSteps(
            "Identify the feature or setting the user is looking for.\n" .
            "Map it to the correct Magento admin menu path.\n" .
            "Provide the navigation path and a brief explanation."
        );
        $agent->setOutput(
            "Always provide the menu path using the format: Menu > Submenu > Page.\n" .
            "Keep answers short and actionable.\n" .
            "If multiple paths exist, list all of them.\n" .
            "Do not include code snippets or technical implementation details unless explicitly asked."
        );

        $this->saveAiAgent->execute($agent);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [
            CreatePageBuilderAgent::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
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
