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

namespace Mageplaza\Core\Block\Adminhtml;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Extensions
 */
class Extensions extends \Magento\Framework\View\Element\Template
{
    /**
     * Cache group Tag
     */
    const CACHE_GROUP = Config::TYPE_IDENTIFIER;

    /**
     * Prefix for cache key of block
     */
    const CACHE_KEY_PREFIX = 'MAGEPLAZA_';

    /**
     * Cache tag
     */
    const CACHE_TAG = 'extensions';

    /**
     * Mageplaza api url to get extension json
     */
    const API_URL = 'https://www.mageplaza.com/api/getVersions.json';

    /**
     * @var string
     */
    protected $_template = 'extensions.phtml';

    /**
     * @var \Magento\Framework\Module\FullModuleList
     */
    private $moduleList;

    /**
     * Extensions constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Module\FullModuleList $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        FullModuleList $moduleList,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->moduleList = $moduleList;

        $this->addData(
            ['cache_lifetime' => 86400, 'cache_tags' => [self::CACHE_TAG]]
        );
    }

    /**
     * @return array
     */
    public function getInstalledModules()
    {
        $mageplza_modules = [];
        foreach ($this->moduleList->getAll() as $moduleName => $info) {
            if (strpos($moduleName, 'Mageplaza') !== false) {
                $mageplza_modules[$moduleName] = $info['setup_version'];
            }
        }

        return $mageplza_modules;
    }

    /**
     * @return bool|mixed|string
     */
    public function getAvailableModules()
    {
        $result = $this->_loadCache();
        if (!$result) {
            try {
                $result = file_get_contents(self::API_URL);
                $this->_saveCache($result);
            } catch (\Exception $e) {
                return false;
            }
        }

        $result = json_decode($result, true); //true return array otherwise object

        return $result;
    }
}
