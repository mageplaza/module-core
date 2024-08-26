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

use Exception;
use Magento\Framework\App\ResourceConnection;
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
     * @var ResourceConnection
     */
    protected $resource;
    /**
     * @var BehaviorSubmit
     */
    protected $behaviorSubmit;

    /**
     * CacheCleanObserver constructor.
     *
     * @param ResourceConnection $resource
     * @param BehaviorSubmit $behaviorSubmit
     */
    public function __construct(
        ResourceConnection $resource,
        BehaviorSubmit $behaviorSubmit
    ) {
        $this->resource       = $resource;
        $this->behaviorSubmit = $behaviorSubmit;
    }

    /**
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $connection = $this->resource->getConnection();

        try {
            // Start a transaction
            $connection->beginTransaction();
            // Data to insert
            $data = $this->behaviorSubmit->getDataFormCache();
            if (empty($data)) {
                return;
            }
            // Insert data into the table
            $connection->insertMultiple('behaviors', $data);

            // Commit the transaction
            $connection->commit();
        } catch (Exception $e) {
            // do nothing
        }
    }
}
