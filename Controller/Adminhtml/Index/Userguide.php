<?php

namespace Mageplaza\Core\Controller\Adminhtml\Index;

class Userguide extends \Magento\Backend\App\Action
{
    public function execute()
    {
        echo '<script type="text/javascript">'.
         'location.href = "https://docs.mageplaza.com/";'.
         '</script>';
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mageplaza_Core::userguide');
    }
}
