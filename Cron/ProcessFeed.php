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

namespace Mageplaza\Core\Cron;

use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\Core\Model\Feed;

/**
 * Class ProcessFeed
 * @package Mageplaza\Core\Cron
 */
class ProcessFeed
{
    /**
     * @var AbstractData
     */
    protected $helper;

    /**
     * ProcessFeed constructor.
     *
     * @param AbstractData $helper
     */
    public function __construct(
        AbstractData $helper
    ) {
        $this->helper = $helper;
    }
    
    public function execute()
    {
        if ($this->helper->isModuleOutputEnabled('Magento_AdminNotification')) {
            /* @var $feedModel Feed */
            $feedModel = $this->helper->createObject(Feed::class);
            $feedModel->checkUpdate();
        }
    }
}
