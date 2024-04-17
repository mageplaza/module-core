<?php


namespace Mageplaza\Core\Block\Adminhtml;


use Magento\Framework\DataObject;

/**
 * Class ProcessBar
 * @package Mageplaza\Core\Block\Adminhtml
 */
class ProcessBar extends \Magento\Framework\View\Element\Template
{
    protected $_template = '/popup/progressbar.phtml';

    /**
     * @var DataObject
     */
    protected $dataProcess;

    /**
     * @param bool $isEnable
     * @param string $url
     * @param array $collection
     *
     * @return ProcessBar
     */
    public function setProcessData(bool $isEnable, string $url, array $collection)
    {

        $data              = [
            'isEnable'   => $isEnable,
            'url'        => $url,
            'collection' => $collection
        ];
        $this->dataProcess = new DataObject($data);

        return $this;
    }

    /**
     * Get Data to process in Js
     *
     * @return DataObject
     */
    public function getDataProcessBar()
    {
        return $this->dataProcess;
    }

}
