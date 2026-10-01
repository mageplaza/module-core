<?php
/** Mageplaza Core — admin extensions overview. */
namespace Mageplaza\Core\Controller\Adminhtml\Extensions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Mageplaza_Core::extensions';
    /** @var PageFactory */
    private $pageFactory;

    public function __construct(Context $context, PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /** @return \Magento\Framework\View\Result\Page */
    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Mageplaza_Core::extensions');
        $page->getConfig()->getTitle()->prepend(__('My Extensions'));
        return $page;
    }
}
