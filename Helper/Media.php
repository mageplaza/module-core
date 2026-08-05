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

use DOMDocument;
use DomainException;
use DOMXPath;
use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Media
 * @package Mageplaza\Core\Helper
 */
class Media extends AbstractData
{
    const TEMPLATE_MEDIA_PATH = 'mageplaza';

    /**
     * SVG elements kept by the sanitizer. Anything else is dropped with its subtree.
     * Names are case-sensitive, matching the SVG spec.
     */
    protected const SVG_ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'metadata', 'switch',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'image',
        'text', 'tspan', 'textPath',
        'linearGradient', 'radialGradient', 'stop', 'pattern', 'clipPath', 'mask', 'marker',
        'filter', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite',
        'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDropShadow',
        'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur',
        'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'feSpecularLighting',
        'feTile', 'feTurbulence',
    ];

    /**
     * SVG attributes kept by the sanitizer, compared lowercase.
     * No scripting, animation or event attribute is listed on purpose.
     */
    protected const SVG_ALLOWED_ATTRIBUTES = [
        'id', 'class', 'style', 'transform', 'viewbox', 'version', 'xmlns', 'xmlns:xlink',
        'x', 'y', 'dx', 'dy', 'width', 'height', 'rx', 'ry', 'cx', 'cy', 'r', 'd', 'points',
        'x1', 'y1', 'x2', 'y2', 'fx', 'fy',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset',
        'stroke-opacity', 'opacity', 'color', 'display', 'visibility', 'overflow',
        'clip-path', 'clip-rule', 'mask', 'filter', 'paint-order',
        'font-family', 'font-size', 'font-weight', 'font-style', 'text-anchor',
        'letter-spacing', 'word-spacing', 'dominant-baseline', 'baseline-shift',
        'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform',
        'spreadmethod', 'patternunits', 'patterncontentunits', 'patterntransform',
        'clippathunits', 'maskunits', 'maskcontentunits', 'filterunits', 'primitiveunits',
        'preserveaspectratio', 'markerwidth', 'markerheight', 'markerunits',
        'refx', 'refy', 'orient', 'marker-start', 'marker-mid', 'marker-end',
        'stddeviation', 'in', 'in2', 'result', 'mode', 'values', 'type', 'operator',
        'href', 'xlink:href',
    ];

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var AdapterFactory
     */
    protected $imageFactory;

    /**
     * Media constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param AdapterFactory $imageFactory
     *
     * @throws FileSystemException
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        AdapterFactory $imageFactory
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->uploaderFactory = $uploaderFactory;
        $this->imageFactory = $imageFactory;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param $data
     * @param string $fileName
     * @param string $type
     * @param null $oldImage
     *
     * @return $this
     */
    public function uploadImage(&$data, $fileName = 'image', $type = '', $oldImage = null)
    {
        if (isset($data[$fileName]['delete']) && $data[$fileName]['delete']) {
            if ($oldImage) {
                try {
                    $this->removeImage($oldImage, $type);
                } catch (Exception $e) {
                    $this->_logger->critical($e->getMessage());
                }
            }
            $data[$fileName] = '';
        } else {
            try {
                $uploader = $this->uploaderFactory->create(['fileId' => $fileName]);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'svg']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);

                $path = $this->getBaseMediaPath($type);

                $image = $uploader->save(
                    $this->mediaDirectory->getAbsolutePath($path)
                );

                if (preg_match('/\.svg$/i', $image['file'])) {
                    $this->sanitizeSvg($path . '/' . ltrim($image['file'], '/'));
                }

                if ($oldImage) {
                    $this->removeImage($oldImage, $type);
                }

                $data[$fileName] = $this->_prepareFile($image['file']);
            } catch (Exception $e) {
                // The uploader raises a DomainException when no file was submitted, which is
                // the normal case for a form saved without picking a new image. Only genuine
                // failures, such as a rejected SVG, are worth a log entry.
                if (!$e instanceof DomainException) {
                    $this->_logger->critical($e->getMessage());
                }
                $data[$fileName] = isset($data[$fileName]['value']) ? $data[$fileName]['value'] : '';
            }
        }

        return $this;
    }

    /**
     * Rewrite an uploaded SVG keeping only allowlisted elements and attributes.
     *
     * Parsing to a DOM first is required, not a style choice: a string/regex filter sees
     * the raw bytes while the browser acts on the parsed value, so "&#106;avascript:"
     * passes a "javascript:" match and still runs. Reading attributes off the DOM gives
     * the decoded value, which is what the policy below is applied to.
     *
     * The file is deleted and an exception thrown when it cannot be made safe, so the
     * caller falls back to the previous image.
     *
     * @param string $relativePath
     *
     * @return void
     * @throws Exception
     */
    protected function sanitizeSvg($relativePath)
    {
        if (!$this->mediaDirectory->isFile($relativePath)) {
            return;
        }

        $content = $this->mediaDirectory->readFile($relativePath);

        // Custom entities allow XXE and billion-laughs expansion during parsing itself,
        // so they are rejected before the parser ever sees them. No icon needs a DTD.
        if (stripos($content, '<!ENTITY') !== false) {
            $this->rejectSvg($relativePath);
        }
        $content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $content);

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // LIBXML_NONET blocks network fetches; LIBXML_NOENT is deliberately NOT set,
        // as it would substitute entities instead of leaving them inert.
        $loaded = $dom->loadXML($content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$dom->documentElement || $dom->documentElement->localName !== 'svg') {
            $this->rejectSvg($relativePath);
        }

        $xpath = new DOMXPath($dom);
        foreach (iterator_to_array($xpath->query('//comment()|//processing-instruction()')) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        foreach (iterator_to_array($dom->getElementsByTagName('*')) as $element) {
            // Skip nodes already detached along with a removed ancestor.
            if (!$element->parentNode) {
                continue;
            }

            if (!in_array($element->localName, self::SVG_ALLOWED_ELEMENTS, true)) {
                $element->parentNode->removeChild($element);
                continue;
            }

            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);

                if (strpos($name, 'on') === 0
                    || !in_array($name, self::SVG_ALLOWED_ATTRIBUTES, true)
                    || (strtolower($attribute->localName) === 'href'
                        && !$this->isSafeSvgUrl($attribute->value, $element->localName))
                    || ($name === 'style' && !$this->isSafeSvgStyle($attribute->value))
                ) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }

        $this->mediaDirectory->writeFile($relativePath, $dom->saveXML());
    }

    /**
     * Delete an SVG that cannot be sanitized and abort the upload.
     *
     * @param string $relativePath
     *
     * @return void
     * @throws Exception
     */
    private function rejectSvg($relativePath)
    {
        if ($this->mediaDirectory->isFile($relativePath)) {
            $this->mediaDirectory->delete($relativePath);
        }

        throw new Exception('The SVG file could not be sanitized and was rejected.');
    }

    /**
     * Decide whether a decoded href value is safe for the element carrying it.
     *
     * @param string $value
     * @param string $elementName
     *
     * @return bool
     */
    private function isSafeSvgUrl($value, $elementName)
    {
        // Browsers ignore whitespace and control characters when reading a scheme,
        // so they are dropped before the scheme is matched.
        $url = strtolower(preg_replace('/[\s\x00-\x20\x7f]+/', '', (string) $value));

        if ($url === '') {
            return true;
        }

        // "use" pulls a subtree into the document, so only same-document fragments.
        if ($elementName === 'use') {
            return strpos($url, '#') === 0;
        }

        // No scheme means a relative path or fragment.
        if (!preg_match('/^([a-z0-9+.\-]+):/', $url, $matches)) {
            return true;
        }

        if ($elementName === 'image') {
            return $matches[1] === 'data'
                ? (bool) preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#', $url)
                : in_array($matches[1], ['http', 'https'], true);
        }

        return in_array($matches[1], ['http', 'https', 'mailto'], true);
    }

    /**
     * Reject inline styles able to load or execute code.
     *
     * @param string $value
     *
     * @return bool
     */
    private function isSafeSvgStyle($value)
    {
        $style = strtolower(preg_replace('/[\s\x00-\x20\x7f]+/', '', (string) $value));

        foreach (['expression', 'javascript:', '@import', 'behavior:', '-moz-binding'] as $token) {
            if (strpos($style, $token) !== false) {
                return false;
            }
        }

        // fill:url(#gradient) is common and harmless; anything url() can reach outside the
        // document is not, so only same-document fragments are allowed.
        return !preg_match('/url\((?!#)/', $style);
    }

    /**
     * @param $file
     * @param $type
     *
     * @return $this
     * @throws FileSystemException
     */
    public function removeImage($file, $type)
    {
        $image = $this->getMediaPath($file, $type);
        if ($this->mediaDirectory->isFile($image)) {
            $this->mediaDirectory->delete($image);
        }

        return $this;
    }

    /**
     * @param $file
     * @param string $type
     *
     * @return string
     */
    public function getMediaPath($file, $type = '')
    {
        return $this->getBaseMediaPath($type) . '/' . $this->_prepareFile($file);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getBaseMediaPath($type = '')
    {
        return trim(static::TEMPLATE_MEDIA_PATH . '/' . $type, '/');
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function _prepareFile($file)
    {
        return ltrim(str_replace('\\', '/', $file), '/');
    }

    /**
     * @param $file
     * @param $size
     * @param string $type
     * @param bool $keepRatio
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function resizeImage($file, $size, $type = '', $keepRatio = true)
    {
        $image = $this->getMediaPath($file, $type);
        if (!($imageSize = $this->correctImageSize($size))) {
            return $this->getMediaUrl($image);
        }
        list($width, $height) = $imageSize;

        $resizeImage = $this->getMediaPath($file, ($type ? $type . '/' : '') . 'resize/' . $width . 'x' . $height);

        /** @var WriteInterface $mediaDirectory */
        $mediaDirectory = $this->getMediaDirectory();
        if ($mediaDirectory->isFile($resizeImage)) {
            $image = $resizeImage;
        } elseif ($mediaDirectory->isExist($mediaDirectory->getAbsolutePath($image))) {
            $imageResize = $this->imageFactory->create();
            $imageResize->open($mediaDirectory->getAbsolutePath($image));
            $imageResize->constrainOnly(true);
            $imageResize->keepTransparency(true);
            $imageResize->keepFrame(false);
            $imageResize->keepAspectRatio($keepRatio);
            $imageResize->resize($width, $height);

            try {
                $imageResize->save($mediaDirectory->getAbsolutePath($resizeImage));

                $image = $resizeImage;
            } catch (Exception $e) {
                $this->_logger->critical($e->getMessage());
            }
        }

        return $this->getMediaUrl($image);
    }

    /**
     * @param $size
     *
     * @return array|bool
     */
    protected function correctImageSize($size)
    {
        if (!$size) {
            return false;
        }

        if (strpos($size, 'x') === false) {
            $width = $height = (int) $size;
        } else {
            list($width, $height) = explode('x', $size);
        }

        if (!$width && !$height) {
            return false;
        }

        return [(int) $width ?: null, (int) $height ?: null];
    }

    /**
     * @param $file
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMediaUrl($file)
    {
        return $this->getBaseMediaUrl() . '/' . $this->_prepareFile($file);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseMediaUrl()
    {
        return rtrim($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/');
    }

    /**
     * @return WriteInterface
     */
    public function getMediaDirectory()
    {
        return $this->mediaDirectory;
    }

    /**
     * @param $path
     *
     * @return $this
     * @throws FileSystemException
     */
    public function removePath($path)
    {
        $pathMedia = $this->mediaDirectory->getRelativePath($path);
        if ($this->mediaDirectory->isDirectory($pathMedia)) {
            $this->mediaDirectory->delete($path);
        }

        return $this;
    }
}
