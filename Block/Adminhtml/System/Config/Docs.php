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

namespace Mageplaza\Core\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Mageplaza\Core\Helper\Validate;

/**
 * Class Message
 * @package Mageplaza\Core\Block\Adminhtml\System\Config
 */
class Docs extends Field
{
    protected $helper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Validate $helper,
        array $data = []
    )
    {
        $this->helper = $helper;

        parent::__construct($context, $data);
    }

    /**
     * Render text
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $moduleName = $originalData['module_name'];
        $lowerCaseName = $this->getLowerCase($moduleName);
        $userGuidePath = $this->helper->getModuleData($moduleName, 'user_guide') ?: $lowerCaseName;

        $html = '<td colspan="3" id="mageplaza-module-docs-id">
                    <div id="mageplaza-module-docs" class="mageplaza-module-messages">
                        <div class="messages">
                            <div class="message message-info">
                                <div data-ui-id="messages-message-info">
                                <ul style="margin: 0 0 0 2em;">
                                    <li><a href="http://docs.mageplaza.com/' . $userGuidePath . '/" target="_blank">' . __('User Guide') . '</a></li>
                                    <li><a href="https://www.mageplaza.com/faqs/" target="_blank">' . __('FAQs') . '</a></li>
                                    <li><a href="http://store.mageplaza.com/my-downloadable-products.html" target="_blank">' . __('Check Latest Version') . '</a></li>
                                </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>';

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @param $name
     *
     * @return string
     */
    private function getLowerCase($name)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', str_replace('Mageplaza_', '', $name)));
    }
}
