<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Controller\Adminhtml\Chat;

use Gtstudio\AiWidgets\Model\Service\AdminChatService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Message extends Action
{
    public const ADMIN_RESOURCE = 'Gtstudio_AiWidgets::management';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param AdminChatService $chatService
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly AdminChatService $chatService
    ) {
        parent::__construct($context);
    }

    /**
     * Handle the admin chat AJAX request.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $message = trim((string) $this->getRequest()->getParam('message'));

        if (empty($message)) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'content' => ''
            ]);
        }

        $history = [];
        $historyJson = $this->getRequest()->getParam('history', '[]');

        if (!empty($historyJson)) {
            $decoded = json_decode($historyJson, true);
            $history = is_array($decoded) ? $decoded : [];
        }

        try {
            $response = $this->chatService->ask($message, $history);

            $result = [
                'success' => true,
                'content' => $response['content'] ?? ''
            ];

            // Include token metadata if available
            if (!empty($response['tokens'])) {
                $result['tokens'] = $response['tokens'];
            }
            if (!empty($response['model'])) {
                $result['model'] = $response['model'];
            }
            if (!empty($response['provider'])) {
                $result['provider'] = $response['provider'];
            }

            return $this->resultJsonFactory->create()->setData($result);
        } catch (\Throwable $e) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'content' => $e->getMessage()
            ]);
        }
    }
}
