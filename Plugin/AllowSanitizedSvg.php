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

namespace Mageplaza\Core\Plugin;

use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Mageplaza\Core\Model\SvgUploadContext;

/**
 * Allows svg through the protected extension check for Mageplaza uploads only.
 *
 * Magento protects svg because an SVG can carry script and nothing in the platform
 * strips it. Mageplaza\Core\Helper\Media does strip it, so the rule is lifted for the
 * upload that helper is saving and for no other. Every other uploader in the
 * installation keeps seeing svg as protected.
 */
class AllowSanitizedSvg
{
    /**
     * @var SvgUploadContext
     */
    private $svgUploadContext;

    /**
     * @param SvgUploadContext $svgUploadContext
     */
    public function __construct(SvgUploadContext $svgUploadContext)
    {
        $this->svgUploadContext = $svgUploadContext;
    }

    /**
     * Let svg past the protected extension check inside a Mageplaza upload.
     *
     * @param NotProtectedExtension $subject
     * @param bool $result
     * @param string $value
     *
     * @return bool
     */
    public function afterIsValid(NotProtectedExtension $subject, $result, $value = '')
    {
        if ($result || !$this->svgUploadContext->isActive()) {
            return $result;
        }

        // svgz is gzipped and the sanitizer cannot read it, so it stays protected.
        return strtolower(trim((string) $value)) === 'svg';
    }
}
