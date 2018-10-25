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

namespace Mageplaza\Core\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

/**
 * Class Menu
 * @package Mageplaza\Core\Model\Config\Backend
 */
class Menu extends Value
{
    protected $_resourceName = \Magento\Config\Model\ResourceModel\Config\Data::class;

    /**
     * {@inheritdoc}
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER);
            $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        }

        return parent::afterSave();
    }
}
