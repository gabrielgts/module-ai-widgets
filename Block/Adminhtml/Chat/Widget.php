<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Block\Adminhtml\Chat;

use Gtstudio\AiConnector\Api\TokenCostServiceInterface;
use Gtstudio\AiConnector\Model\Config\ConfigProvider;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Widget extends Template
{
    /**
     * @param Context $context
     * @param TokenCostServiceInterface $tokenCostService
     * @param ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly TokenCostServiceInterface $tokenCostService,
        private readonly ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the AJAX endpoint URL for the chat controller.
     *
     * @return string
     */
    public function getChatEndpointUrl(): string
    {
        return $this->getUrl('aiwidgets/chat/message');
    }

    /**
     * Get the current form key for CSRF protection.
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Return the pricing table as a JSON-encoded string for injection into JS.
     */
    public function getPricingTableJson(): string
    {
        return (string) json_encode($this->tokenCostService->getPricingTable());
    }

    /**
     * Return the configured model identifier.
     */
    public function getDefaultModel(): string
    {
        return $this->configProvider->getModel();
    }
}
