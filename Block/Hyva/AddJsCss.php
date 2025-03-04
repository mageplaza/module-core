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

namespace Mageplaza\Core\Block\Hyva;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Filesystem\Io\File;

class AddJsCss extends Template
{

    /**
     * @var File
     */
    protected $fileIo;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param File $fileIo
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        File $fileIo,
        array $data = []
    ) {
        $this->fileIo = $fileIo;
        parent::__construct($context, $data);
    }
    /**
     * Set Files
     *
     * @param array $files
     *
     * @return void
     */
    public function setFiles($files)
    {
        $this->setData('files', array_merge($this->getData('files')?? [], $files));
    }

    /**
     * Get path info
     *
     * @param string $file
     * @return array
     */
    public function getFilePathInfo($file)
    {
        return $this->fileIo->getPathInfo($file);
    }
}
