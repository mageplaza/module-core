<?php
/** Mageplaza Core — explicit refresh with Magento form key and ACL protection. */
namespace Mageplaza\Core\Controller\Adminhtml\Extensions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Mageplaza\Core\Model\License\Provider;
use Psr\Log\LoggerInterface;

class Refresh extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Mageplaza_Core::extensions';
    /** @var Provider */
    private $provider;
    /** @var JsonFactory */
    private $jsonFactory;
    /** @var Validator */
    private $formKeyValidator;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Context $context,
        Provider $provider,
        JsonFactory $jsonFactory,
        Validator $formKeyValidator,
        LoggerInterface $logger
    ) {
        $this->provider = $provider;
        $this->jsonFactory = $jsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /** @return \Magento\Framework\Controller\Result\Json */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setHttpResponseCode(400)->setData([
                'success' => false, 'message' => __('Invalid form key. Please reload the page.')
            ]);
        }
        try {
            $refreshed = $this->provider->refresh();
            if (!$refreshed) {
                $retryAfter = $this->provider->getRefreshRetryAfter();
                return $result->setData([
                    'success' => false,
                    'reason' => $retryAfter ? 'cooldown' : 'in_progress',
                    'retry_after' => max(1, $retryAfter)
                ]);
            }
            $listing = $this->_view->getLayout()
                ->createBlock(\Mageplaza\Core\Block\Adminhtml\Extensions\Listing::class)
                ->setTemplate('Mageplaza_Core::extensions/listing.phtml');
            return $result->setData([
                'success' => true, 'retry_after' => $this->provider->getRefreshRetryAfter(),
                'html' => $listing->toHtml()
            ]);
        } catch (LocalizedException $exception) {
            return $result->setData(['success' => false, 'message' => $exception->getMessage(),
                'retry_after' => $this->provider->getRefreshRetryAfter()]);
        } catch (\Exception $exception) {
            $this->logger->warning('Mageplaza extension check failed: refresh_error');
            return $result->setData(['success' => false, 'message' => __('Unable to check for updates. Please try again later.'),
                'retry_after' => $this->provider->getRefreshRetryAfter()]);
        }
    }
}
