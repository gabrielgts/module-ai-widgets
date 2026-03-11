<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Model\Service;

use Gtstudio\AiConnector\Api\Data\AiRequestInterfaceFactory;
use Gtstudio\AiConnector\Model\Client\NeuronClient;
use Gtstudio\AiConnector\Model\Config\ConfigProvider;

class PageBuilderGenerator
{
    private const SYSTEM_PROMPT =
        'You are an expert Magento 2 PageBuilder HTML content generator.' . "\n" .
        'Generate clean, semantic HTML5 based on the user\'s description.' . "\n" .
        'Output ONLY the raw HTML — no markdown code fences, no explanations, no text outside the HTML.' . "\n" .
        'Use semantic HTML5 elements (<section>, <article>, <header>, <div>, <p>, <h1>–<h6>, <ul>, <li>, <a>).' . "\n" .
        'Apply inline CSS styles for colors, spacing, typography, and layout.' . "\n" .
        'The HTML must be self-contained and ready to paste into a PageBuilder HTML content block.' . "\n" .
        'IMPORTANT: Your entire response must fit within %d output tokens. ' .
        'Always produce a complete, valid HTML block — never truncate or leave tags unclosed. ' .
        'If the description is complex, reduce visual detail rather than cutting the output short. ' .
        'Never return an empty response.';

    public function __construct(
        private readonly NeuronClient $neuronClient,
        private readonly AiRequestInterfaceFactory $requestFactory,
        private readonly ConfigProvider $config
    ) {
    }

    /**
     * Generate PageBuilder HTML content via a single-turn AI request (no history).
     *
     * @param string $prompt
     * @return string
     * @throws \Throwable
     */
    public function generate(string $prompt): string
    {
        $maxTokens  = $this->config->getMaxTokens();
        $systemPrompt = sprintf(self::SYSTEM_PROMPT, $maxTokens);

        $request = $this->requestFactory->create([
            'data' => [
                'prompt' => $prompt,
                'rules'  => $systemPrompt,
            ],
        ]);

        $response = $this->neuronClient->send($request);
        return (string) $response->getContent();
    }
}
