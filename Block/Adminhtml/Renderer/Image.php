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

namespace Mageplaza\Core\Block\Adminhtml\Renderer;

/**
 * Renderer image for admin form
 * Add media path to url
 *
 * Class Image
 * @package Mageplaza\Core\Block\Adminhtml\Renderer
 */
class Image extends \Magento\Framework\Data\Form\Element\Image
{
    /**
     * Get image preview url
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUrl()
    {
        $url = parent::_getUrl();

        if ($this->getPath()) {
            $url = $this->getPath() . '/' . trim($url, '/');
        }

        return $url;
    }
}
