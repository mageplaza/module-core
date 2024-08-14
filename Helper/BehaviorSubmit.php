<?php


namespace Mageplaza\Core\Helper;


use GuzzleHttp\Client;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Mageplaza\Core\Model\Behavior;
use Mageplaza\Core\Model\ResourceModel\Behavior as ResourceModel;
use Throwable;
use Zend_Log;
use Zend_Log_Writer_Stream;

/**
 * Class BehaviorSubmit
 * Mageplaza\Core\Helper
 */
class BehaviorSubmit
{
    /**
     * @var Behavior
     */
    private $behavior;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * BehaviorSubmit constructor.
     *
     * @param Behavior $behavior
     * @param ResourceModel $resourceModel
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Behavior $behavior,
        ResourceModel $resourceModel,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->scopeConfig   = $scopeConfig;
        $this->configWriter  = $configWriter;
        $this->behavior      = $behavior;
        $this->resourceModel = $resourceModel;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * SaveData
     *
     * @throw \Magento\Framework\Exception\AlreadyExistsException
     * @param array $behaviorData
     */
    public function saveData(array $behaviorData)
    {
        try {
            $this->behavior->addData(['content' => AbstractData::jsonEncode($behaviorData)]);
            $this->resourceModel->save($this->behavior);
        } catch (\Exception $e) {
            //do no thing
        }
    }

    /**
     * @return string
     */
    public function getConfigCountry()
    {
        $country = $this->scopeConfig->getValue('mp_core/general/country', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        if (!$country) {
            return $this->getCountryByIp();
        }

        return $country;
    }

    /**
     * @return string
     */
    public function getCountryByIp()
    {
        try {
            $client   = new Client();
            $response = $client->request('GET', 'https://api.ipify.org?format=json');
            $data     = json_decode($response->getBody(), true);

            $ip = $data['ip'] ?? '';
            if (!$ip) {
                return '';
            }
            $response    = $client->request('GET', 'http://www.geoplugin.net/json.gp?ip=' . $ip);
            $data        = json_decode($response->getBody(), true);
            $countryCode = $data['geoplugin_countryCode'] ?? 'NULL';
            $this->setValue($countryCode);
        } catch (\Exception $e) {
            $countryCode = 'NULL';
            //do no thing
        }


        return $countryCode;

    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        try {
            $scope   = ScopeConfigInterface::SCOPE_TYPE_DEFAULT; // Scope (default, website, store)
            $scopeId = 0; // Scope ID (0 for default)
            $this->configWriter->save('mp_core/general/country', $value, $scope, $scopeId);
            // Clear relevant cache types
            $this->cacheTypeList->invalidate(['config']);

            return true;
        } catch (\Exception $e) {
            // Handle exception
            return false;
        }
    }

    /**
     * SubmitData
     *
     * @param array $behaviors
     */
    public static function submitData(array $behaviors)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://http://extensions_behavior_data_api.com//api/records");
            curl_setopt($ch, CURLOPT_POST, 1);
            $behaviorData['behaviors'] = $behaviors;
            $postData                  = http_build_query($behaviorData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $writer = new Zend_Log_Writer_Stream(BP . '/var/log/submit_data.log');
            $logger = new Zend_Log();
            $logger->addWriter($writer);
            $logger->info($response);

        } catch (Throwable $e) {
            //Do no thing
        }
    }

    /**
     * RandomString
     *
     * @param int $length
     *
     * @return string
     */
    public static function randomString($length = 15)
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
