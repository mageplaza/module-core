<?php

namespace Mageplaza\Core\Model;

use Magento\Framework\Model\AbstractModel;
use Mageplaza\Core\Model\ResourceModel\Behavior as ResourceModel;

class Behavior extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'behaviors_model';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
