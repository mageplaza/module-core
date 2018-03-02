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

namespace Mageplaza\Core\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class NoticeType
 * @package Mageplaza\Core\Model\Config\Source
 */
class NoticeType implements ArrayInterface
{
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_NEWUPDATE = 'new_update';
    const TYPE_MARKETING = 'marketing';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->toArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::TYPE_ANNOUNCEMENT => __('Announcement'),
            self::TYPE_NEWUPDATE => __('New & Update extensions'),
            self::TYPE_MARKETING => __('Promotions ')
        ];
    }
}
