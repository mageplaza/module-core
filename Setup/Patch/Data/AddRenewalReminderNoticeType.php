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

namespace Mageplaza\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mageplaza\Core\Model\Config\Source\NoticeType;

class AddRenewalReminderNoticeType implements DataPatchInterface
{
    private $setup;

    public function __construct(ModuleDataSetupInterface $setup)
    {
        $this->setup = $setup;
    }

    public function apply()
    {
        $connection = $this->setup->getConnection();
        $table = $this->setup->getTable('core_config_data');
        $rows = $connection->fetchPairs($connection->select()->from($table, ['config_id', 'value'])
            ->where('path = ?', 'mageplaza/general/notice_type'));
        foreach ($rows as $id => $value) {
            $types = array_filter(array_map('trim', explode(',', (string) $value)));
            if (!in_array(NoticeType::TYPE_RENEWAL, $types, true)) {
                $types[] = NoticeType::TYPE_RENEWAL;
                $connection->update($table, ['value' => implode(',', $types)], ['config_id = ?' => $id]);
            }
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
