<?php

namespace Mageplaza\Core\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Behavior extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'behaviors_resource_model';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('behaviors', 'entity_id');
        $this->_useIsObjectNew = true;
    }
}
