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
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AbstractData
 * @package Mageplaza\Core\Helper
 */
class AbstractData extends AbstractHelper
{
	/**
	 * @type array
	 */
	protected $_data = [];

	/**
	 * @type \Magento\Store\Model\StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @type \Magento\Framework\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \Magento\Framework\App\Helper\Context $context
	 * @param \Magento\Framework\ObjectManagerInterface $objectManager
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 */
	public function __construct(
		Context $context,
		ObjectManagerInterface $objectManager,
		StoreManagerInterface $storeManager
	)
	{
		$this->objectManager = $objectManager;
		$this->storeManager  = $storeManager;

		parent::__construct($context);
	}

	/**
	 * @param $field
	 * @param null $storeId
	 * @return mixed
	 */
	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field,
			ScopeInterface::SCOPE_STORE,
			$storeId
		);
	}

	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function setData($name, $value)
	{
		$this->_data[$name] = $value;

		return $this;
	}

	/**
	 * @param $name
	 * @return null
	 */
	public function getData($name)
	{
		if (array_key_exists($name, $this->_data)) {
			return $this->_data[$name];
		}

		return null;
	}

	/**
	 * @return mixed
	 */
	public function getCurrentUrl()
	{
		$model = $this->objectManager->get('Magento\Framework\UrlInterface');

		return $model->getCurrentUrl();
	}

	/**
	 * @param $path
	 * @param array $arguments
	 * @return mixed
	 */
	public function createObject($path, $arguments = [])
	{
		return $this->objectManager->create($path, $arguments);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public function getObject($path)
	{
		return $this->objectManager->get($path);
	}
}