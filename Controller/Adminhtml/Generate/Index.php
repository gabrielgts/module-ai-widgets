<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Gtstudio\AiWidgets\Model\Service\PageBuilderGenerator;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Gtstudio_AiWidgets::management';

    public function __construct(
        Action\Context $context,
        private JsonFactory $resultJsonFactory,
        private PageBuilderGenerator $generator,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $prompt = $this->getRequest()->getParam('prompt');

        if (empty($prompt)) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'error' => (string) __('Please enter a prompt.')
            ]);
        }

        try {
            $content = $this->generator->generate($prompt);
        } catch (LocalizedException $e) {
            $this->logger->error('PageBuilder AI generation failed: ' . $e->getMessage());
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('PageBuilder AI generation error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'error' => (string) __('AI generation failed. Please check the module configuration.' . $e->getMessage())
            ]);
        }

        if (empty(trim($content))) {
            $this->logger->warning('PageBuilder AI returned empty content for prompt: ' . $prompt);
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'error' => (string) __('The AI returned an empty response. Please verify the page_builder agent instructions in the admin panel.')
            ]);
        }

        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'content' => $content
        ]);
    }
}
