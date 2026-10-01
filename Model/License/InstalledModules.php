<?php
/** Mageplaza Core — installed extensions for the admin overview. */
namespace Mageplaza\Core\Model\License;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Mageplaza\Core\Helper\Validate;

class InstalledModules
{
    /** @var Validate */
    private $validate;
    /** @var ComponentRegistrarInterface */
    private $registrar;
    /** @var ReadFactory */
    private $readFactory;
    /** @var array|null */
    private $modules;

    public function __construct(Validate $validate, ComponentRegistrarInterface $registrar, ReadFactory $readFactory)
    {
        $this->validate = $validate;
        $this->registrar = $registrar;
        $this->readFactory = $readFactory;
    }

    /** @return array */
    public function getList()
    {
        if ($this->modules !== null) {
            return $this->modules;
        }
        $this->modules = [];
        foreach ($this->validate->getModuleList() as $module) {
            if ($module === 'Mageplaza_Core') {
                continue;
            }
            try {
                $path = $this->registrar->getPath(ComponentRegistrar::MODULE, $module);
                if (!$path) {
                    continue;
                }
                $data = json_decode($this->readFactory->create($path)->readFile('composer.json'), true);
                if (!is_array($data) || empty($data['name']) || !is_string($data['name'])
                    || empty($data['version']) || !is_string($data['version'])) {
                    continue;
                }
                $package = $data['name'];
                $label = str_replace(['mageplaza/magento-2-', 'mageplaza/module-', 'mageplaza/', '-extension'], '', $package);
                $row = [
                    'package' => $package,
                    'module' => $module,
                    'label' => ucwords(str_replace('-', ' ', $label)),
                    'version' => $data['version'],
                    'key' => '',
                    'section' => false,
                    'release_url' => null
                ];
                // Site-specific modules may have no configuration helper.
                try {
                    $row['section'] = $this->validate->getConfigModulePath($module);
                    $row['key'] = (string) $this->validate->getModuleData($module, 'product_key');
                    $row['release_url'] = $this->validate->getDocUrl($module, 'change_log');
                } catch (\Exception $exception) {
                    // Keep the installed version even when module configuration is unavailable.
                }
                $this->modules[$package] = $row;
            } catch (\Exception $exception) {
                continue;
            }
        }
        return $this->modules;
    }

    public function reset()
    {
        $this->modules = null;
    }

    /** @return array */
    public function getPayloadModules()
    {
        return array_column($this->getList(), 'version', 'package');
    }

    /** @return array */
    public function getPayloadKeys()
    {
        return array_column($this->getList(), 'key', 'package');
    }
}
