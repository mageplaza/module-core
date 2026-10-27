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

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class UpdatesLink extends Template implements RendererInterface
{
    protected $_template = 'Mageplaza_Core::system/config/updates-link.phtml';

    public function render(AbstractElement $element)
    {
        return $this->_authorization->isAllowed('Mageplaza_Core::extensions') ? $this->toHtml() : '';
    }

    /** @return string */
    public function getUpdatesUrl()
    {
        return $this->getUrl('mpcore/extensions/index');
    }
}
