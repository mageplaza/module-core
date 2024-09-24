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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mageplaza\Core\Helper\BehaviorSubmit;

/**
 * Class CacheCleanObserver
 * Mageplaza\Core\Observer
 */
class CacheCleanObserver implements ObserverInterface
{
    /**
     * @var BehaviorSubmit
     */
    protected $behaviorSubmit;

    /**
     * CacheCleanObserver constructor.
     *
     * @param BehaviorSubmit $behaviorSubmit
     */
    public function __construct(
        BehaviorSubmit $behaviorSubmit
    ) {
        $this->behaviorSubmit = $behaviorSubmit;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->behaviorSubmit->saveBehaviors();
    }
}
