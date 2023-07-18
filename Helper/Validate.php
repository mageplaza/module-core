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

namespace Mageplaza\Core\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class AbstractData
 * @package Mageplaza\Core\Helper
 */
class Validate extends AbstractData
{
    const MODULE_TYPE_FREE = 1;
    const MODULE_TYPE_PAID = 2;
    //    const DEV_ENV = ['localhost', 'dev', '127.0.0.1', '192.168.', 'demo.'];
    const DEV_ENV = [];

    /**
     * @var array
     */
    protected $configModulePath = [];

    /**
     * @var array
     */
    protected $_mageplazaModules;

    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * Validate constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList
    ) {
        $this->_moduleList = $moduleList;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param $moduleName
     *
     * @return bool
     */
    public function needActive($moduleName)
    {
        $type = $this->getModuleType($moduleName);

        return $type && $type === self::MODULE_TYPE_FREE;
    }

    /**
     * @param $moduleName
     *
     * @return mixed
     */
    public function getModuleType($moduleName)
    {
        return (int) $this->getModuleData($moduleName, 'type') ?: self::MODULE_TYPE_PAID;
    }

    /**
     * @param $moduleName
     * @param string $field
     *
     * @return array|mixed
     */
    public function getModuleData($moduleName, $field = '')
    {
        $configModulePath = $this->getConfigModulePath($moduleName);

        return $this->getConfigValue($configModulePath . '/module/' . $field);
    }

    /**
     * @param $moduleName
     *
     * @return bool
     */
    public function getConfigModulePath($moduleName)
    {
        if (!isset($this->configModulePath[$moduleName])) {
            $this->configModulePath[$moduleName] = false;

            $helperClassName = str_replace('_', '\\', $moduleName) . '\Helper\Data';
            if (class_exists($helperClassName)) {
                $helper = $this->objectManager->get($helperClassName);
                if ($helper instanceof AbstractData) {
                    $this->configModulePath[$moduleName] = $helper::CONFIG_MODULE_PATH;
                }
            }
        }

        return $this->configModulePath[$moduleName];
    }

    /**
     * @param $moduleName
     *
     * @return bool
     */
    public function isModuleActive($moduleName)
    {
        return $this->getModuleData($moduleName, 'active') && $this->getModuleData($moduleName, 'product_key');
    }

    /**
     * @param $moduleName
     *
     * @return string
     */
    public function getModuleCheckbox($moduleName)
    {
        $create = $this->getModuleData($moduleName, 'create');
        if ($create === null) {
            $create = 1;
        }

        $subscribe = $this->getModuleData($moduleName, 'subscribe');
        if ($subscribe === null) {
            $subscribe = 1;
        }

        return self::jsonEncode([
            'create'    => (int) $create,
            'subscribe' => (int) $subscribe
        ]);
    }

    /**
     * @return array
     */
    public function getModuleList()
    {
        if ($this->_mageplazaModules === null) {
            $this->_mageplazaModules = [];

            $allowList = true;
            if (count(self::DEV_ENV)) {
                $hostName = $this->_urlBuilder->getBaseUrl();
                foreach (self::DEV_ENV as $env) {
                    if (strpos($hostName, $env) !== false) {
                        $allowList = false;
                        break;
                    }
                }
            }

            if ($allowList) {
                $moduleList = $this->_moduleList->getNames();
                foreach ($moduleList as $name) {
                    if (strpos($name, 'Mageplaza_') === false) {
                        continue;
                    }

                    $this->_mageplazaModules[] = $name;
                }
            }
        }

        return $this->_mageplazaModules;
    }
}
