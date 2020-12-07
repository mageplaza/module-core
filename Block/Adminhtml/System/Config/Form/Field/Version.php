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

namespace Mageplaza\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\PackageInfoFactory;

/**
 * Backend system config datetime field renderer
 */
class Version extends Field
{
    /**
     * @var PackageInfoFactory
     */
    protected $_packageInfoFactory;

    /**
     * Version constructor.
     *
     * @param Context $context
     * @param PackageInfoFactory $packageInfoFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        PackageInfoFactory $packageInfoFactory,
        array $data = []
    ) {
        $this->_packageInfoFactory = $packageInfoFactory;

        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $packageInfo = $this->_packageInfoFactory->create();
        $version = $packageInfo->getVersion($originalData['module_name']);

        return '<div class="control-value special">' . $version . '</div>';
    }

    /**
     * @param AbstractElement $element
     * @param string $html
     *
     * @return string
     */
    protected function _decorateRowHtml(AbstractElement $element, $html)
    {
        return '<tr id="row_' . $element->getHtmlId() . '" class="row_mageplaza_module_version">' . $html . '</tr>';
    }
}
