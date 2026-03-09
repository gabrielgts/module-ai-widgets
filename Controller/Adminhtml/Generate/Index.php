<?php
declare(strict_types=1);

namespace Gtstudio\AiWidgets\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Gtstudio\AiWidgets\Model\Service\PageBuilderGenerator;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Gtstudio_AiWidgets::management';

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param PageBuilderGenerator $generator
     */
    public function __construct(
        Action\Context $context,
        private JsonFactory $resultJsonFactory,
        private PageBuilderGenerator $generator
    ) {
        parent::__construct($context);
    }

    /**
     * @return Json
     * @throws \Throwable
     */
    public function execute()
    {
        $prompt = $this->getRequest()->getParam('prompt');

        if (empty($prompt)) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'content' => ''
            ]);
        }

        $content = $this->generator->generate($prompt);
        return $this->resultJsonFactory->create()->setData([
            'success' => true,
            'content' => $content
        ]);
    }
}
