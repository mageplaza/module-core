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

use GuzzleHttp\Client;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mageplaza\Core\Model\Behavior;
use Mageplaza\Core\Model\ResourceModel\Behavior as ResourceModel;
use Throwable;

/**
 * Class BehaviorSubmit
 * Mageplaza\Core\Helper
 */
class BehaviorSubmit
{
    const  CACHE_KEY = 'mp_core_behavior';

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
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * BehaviorSubmit constructor.
     *
     * @param Behavior $behavior
     * @param ResourceModel $resourceModel
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Behavior $behavior,
        ResourceModel $resourceModel,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig   = $scopeConfig;
        $this->configWriter  = $configWriter;
        $this->behavior      = $behavior;
        $this->resourceModel = $resourceModel;
        $this->cacheTypeList = $cacheTypeList;
        $this->cache         = $cache;
        $this->serializer    = $serializer;
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
     * SetValue of Config Systems
     *
     * @param string $value
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
            curl_exec($ch);
            curl_close($ch);
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

    /**
     * SaveToCache
     *
     * @param array $behaviorData
     */
    public function saveToCache(array $behaviorData)
    {
        // Use SerializerInterface to serialize the data
        $serializedData = $this->serializer->serialize($behaviorData);
        $this->cache->save($serializedData, self::CACHE_KEY, [], 86400); // Lifetime in seconds
    }

    /**
     * GetDataFormCache
     *
     * @return array|null
     */
    public function getDataFormCache()
    {
        // Retrieve data from cache using the cache key
        $cachedData = $this->cache->load(self::CACHE_KEY);

        if ($cachedData) {
            $data = $this->serializer->unserialize($cachedData);

            return $data;
        }

        return null;
    }

    /**
     * ClearCacheBehavior
     */
    public function clearCacheBehavior()
    {
        $this->cache->remove(self::CACHE_KEY);
    }
}
