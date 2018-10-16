<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Observer;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mageplaza\Core\Helper\AbstractData;

/**
 * Class PredispatchAdminActionControllerObserver
 * @package Mageplaza\Core\Observer
 */
class PredispatchAdminActionControllerObserver implements ObserverInterface
{
    /**
     * @type Session
     */
    protected $_backendAuthSession;

    /**
     * @var AbstractData
     */
    protected $helper;

    /**
     * PredispatchAdminActionControllerObserver constructor.
     * @param Session $backendAuthSession
     * @param AbstractData $helper
     */
    public function __construct(
        Session $backendAuthSession,
        AbstractData $helper
    )
    {
        $this->_backendAuthSession = $backendAuthSession;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->_backendAuthSession->isLoggedIn()
            && $this->helper->isModuleOutputEnabled('Magento_AdminNotification')
        ) {
            /* @var $feedModel \Mageplaza\Core\Model\Feed */
            $feedModel = $this->helper->createObject(\Mageplaza\Core\Model\Feed::class);
            $feedModel->checkUpdate();
        }
    }
}
