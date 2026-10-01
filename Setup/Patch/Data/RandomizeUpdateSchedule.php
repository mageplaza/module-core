<?php
namespace Mageplaza\Core\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RandomizeUpdateSchedule implements DataPatchInterface
{
    const XML_PATH = 'crontab/default/jobs/core_get_update/schedule/cron_expr';

    private $scopeConfig;
    private $configWriter;

    public function __construct(ScopeConfigInterface $scopeConfig, WriterInterface $configWriter)
    {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    public function apply()
    {
        if (!$this->scopeConfig->getValue(self::XML_PATH)) {
            $this->configWriter->save(self::XML_PATH, sprintf('%d %d * * *', random_int(0, 59), random_int(0, 23)));
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
