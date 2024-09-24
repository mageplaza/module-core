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
namespace Mageplaza\Core\Plugin;

use Magento\Framework\App\Cache\Manager;
use Mageplaza\Core\Helper\BehaviorSubmit;

/**
 * Class CacheFlushPlugin
 * Mageplaza\Core\Plugin
 */
class CacheFlushPlugin
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
     * BeforeFlush
     *
     * @param Manager $subject
     * @param array $types
     */
    public function beforeFlush(Manager $subject, array $types)
    {
        $this->behaviorSubmit->saveBehaviors();
    }

    /**
     * BeforeFlush
     *
     * @param Manager $subject
     * @param array $types
     */
    public function beforeClean(Manager $subject, array $types)
    {
        $this->behaviorSubmit->saveBehaviors();
    }
}
