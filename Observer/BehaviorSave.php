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

namespace Mageplaza\Core\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\Core\Helper\BehaviorSubmit;

/**
 * Class BehaviorSave
 * Mageplaza\Core\Observer
 */
class BehaviorSave implements ObserverInterface
{
    /**
     * @var BehaviorSubmit
     */
    private $behaviorSave;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * BehaviorSave constructor.
     *
     * @param BehaviorSubmit $behaviorSave
     * @param RequestInterface $request
     */
    public function __construct(
        BehaviorSubmit $behaviorSave,
        RequestInterface $request
    ) {
        $this->request      = $request;
        $this->behaviorSave = $behaviorSave;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $moduleName = $this->request->getControllerModule();
        if (!$moduleName) {
            return;
        }
        $section     = $this->request->getParam('section') ?: '';
        $sections    = ['smtp'];
        $activity_id = 1;
        if (!str_contains($moduleName, 'Mageplaza')
            ||
            !(str_contains($moduleName, 'Magento_Config') && in_array($section, $sections))) {

            return;
        }

        $requestPath = $this->request->getPathInfo();
        $data        = [
            'domain'          => $this->request->getServer('HTTP_HOST'),
            'record_id'       => BehaviorSubmit::randomString(),
            'country'         => $this->behaviorSave->getConfigCountry(),
            'path'            => $requestPath,
            'extensions_name' => $moduleName,
            'version_module'  => $moduleName,
            'activity_id'     => $activity_id,
            'new_data'        => AbstractData::jsonEncode($this->request->getParams()),
            'old_data'        => AbstractData::jsonEncode([]),
        ];
        $this->behaviorSave->saveToCache($data);
    }
}
