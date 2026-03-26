<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Setup\Patch\Data;

use Gtstudio\AiAgents\Api\Data\AiAgentInterface;
use Gtstudio\AiAgents\Api\Data\AiAgentInterfaceFactory;
use Gtstudio\AiAgents\Api\GetAiAgentByCodeInterface;
use Gtstudio\AiAgents\Api\SaveAiAgentInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdatePageBuilderAgentInstructions implements DataPatchInterface
{
    private const AGENT_CODE = 'page_builder';

    private const BACKGROUND = <<<'PROMPT'
You are an expert Magento 2 PageBuilder HTML content generator.
Your sole task is to output raw HTML based on the user's description.
You must never include explanations, markdown code fences, or any text outside the HTML.
PROMPT;

    private const STEPS = <<<'PROMPT'
Read the user's description carefully to understand the layout, colors, and content requested.
Plan the HTML structure using semantic HTML5 elements.
Write the complete HTML with inline styles so it works in any PageBuilder context.
PROMPT;

    private const OUTPUT = <<<'PROMPT'
Output only valid HTML5 — no markdown, no commentary, no preamble.
Use semantic elements: <section>, <article>, <header>, <div>, <p>, <h1>–<h6>, <ul>, <li>, <a>, <img>.
Apply inline CSS styles for colors, spacing, typography, and layout.
Ensure the HTML is self-contained and renderable without external stylesheets.
PROMPT;

    /**
     * @param GetAiAgentByCodeInterface $getAiAgentByCode
     * @param SaveAiAgentInterface $saveAiAgent
     * @param AiAgentInterfaceFactory $agentFactory
     */
    public function __construct(
        private readonly GetAiAgentByCodeInterface $getAiAgentByCode,
        private readonly SaveAiAgentInterface $saveAiAgent,
        private readonly AiAgentInterfaceFactory $agentFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        try {
            $agent = $this->getAiAgentByCode->execute(self::AGENT_CODE);
        } catch (NoSuchEntityException) {
            $agent = $this->agentFactory->create();
            $agent->setCode(self::AGENT_CODE);
            $agent->setDescription('Generates clean semantic HTML content for Magento PageBuilder.');
        }

        $agent->setBackground(trim(self::BACKGROUND));
        $agent->setSteps(trim(self::STEPS));
        $agent->setOutput(trim(self::OUTPUT));

        $this->saveAiAgent->execute($agent);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [CreatePageBuilderAgent::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
