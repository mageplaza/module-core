<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Core
 * @copyright   Copyright (c) 2016-2018 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class PredispatchAdminActionControllerObserver
 * @package Mageplaza\Core\Observer
 */
class PredispatchAdminActionControllerObserver implements ObserverInterface
{
    /**
     * @type \Mageplaza\Core\Model\FeedFactory
     */
    protected $_feedFactory;

    /**
     * @type \Magento\Backend\Model\Auth\Session
     */
    protected $_backendAuthSession;

    /**
     * @param \Mageplaza\Core\Model\FeedFactory $feedFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Mageplaza\Core\Model\FeedFactory $feedFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession
    )
    {
        $this->_feedFactory = $feedFactory;
        $this->_backendAuthSession = $backendAuthSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_backendAuthSession->isLoggedIn()) {
            /* @var $feedModel \Mageplaza\Core\Model\Feed */
            $feedModel = $this->_feedFactory->create();
            $feedModel->checkUpdate();
        }
    }
}
