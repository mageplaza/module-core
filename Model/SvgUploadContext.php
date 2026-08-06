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

namespace Mageplaza\Core\Model;

/**
 * Marks the short window in which Mageplaza itself is saving an upload.
 *
 * Magento lists svg in general/file/protected_extensions, a single setting shared by
 * every uploader in the installation. Relaxing it in config would allow svg into
 * customer file attributes, downloadable content and catalog import as well, none of
 * which sanitize what they receive. This flag lets the plugin relax the rule for the
 * one call that is followed by Mageplaza\Core\Helper\Media::sanitizeSvg().
 */
class SvgUploadContext
{
    /**
     * Nesting depth rather than a boolean, so that an inner upload leaving the context
     * does not switch it off for the outer one that is still running.
     *
     * @var int
     */
    private $depth = 0;

    /**
     * Mark the start of a Mageplaza upload.
     *
     * @return void
     */
    public function enter()
    {
        $this->depth++;
    }

    /**
     * Mark the end of a Mageplaza upload.
     *
     * @return void
     */
    public function leave()
    {
        if ($this->depth > 0) {
            $this->depth--;
        }
    }

    /**
     * Whether a Mageplaza upload is currently being saved.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->depth > 0;
    }
}
