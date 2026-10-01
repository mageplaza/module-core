<?php
/** Mageplaza Core — ACL and form-key guarded admin relay for My Extensions clicks. */
namespace Mageplaza\Core\Controller\Adminhtml\Extensions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Mageplaza\Core\Model\License\EventReporter;

class Track extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mageplaza_Core::extensions';

    private $reporter;
    private $jsonFactory;
    private $formKeyValidator;

    public function __construct(
        Context $context,
        EventReporter $reporter,
        JsonFactory $jsonFactory,
        Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->reporter = $reporter;
        $this->jsonFactory = $jsonFactory;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setHttpResponseCode(400)->setData(['success' => false]);
        }
        try {
            $success = $this->reporter->send(
                $this->getRequest()->getParam('event_name'),
                $this->getRequest()->getParam('event_id'),
                $this->getRequest()->getParam('package'),
                $this->getRequest()->getParam('banner_id')
            );
        } catch (\Throwable $exception) {
            $success = false;
        }
        return $result->setData(['success' => $success]);
    }
}
