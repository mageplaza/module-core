<?php

namespace Mageplaza\Core\Model\ResourceModel\Behavior;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mageplaza\Core\Model\Behavior as Model;
use Mageplaza\Core\Model\ResourceModel\Behavior as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'behaviors_collection';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
