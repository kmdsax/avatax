<?php
/**
 * OnePica_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OnePica
 * @package    OnePica_AvaTax
 * @author     OnePica Codemaster <codemaster@onepica.com>
 * @copyright  Copyright (c) 2016 One Pica, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Class OnePica_AvaTax_Helper_LandedCost
 */
class OnePica_AvaTax_Helper_LandedCost extends Mage_Core_Helper_Abstract
{
    /**
     *  Landed Cost Product Group Tab
     */
    const AVATAX_PRODUCT_GROUP_LANDED_COST = 'AvaTax Landed Cost';

    /**
     *  HS Code product attribute
     */
    const AVATAX_PRODUCT_LANDED_COST_ATTR_HSCODE = 'avatax_lc_hs_code';

    /**
     *  HS Code product weight
     */
    const AVATAX_PRODUCT_LANDED_COST_ATTR_UNIT_OF_WEIGHT = 'avatax_lc_unit_of_weight';

    /**
     *  Landed Cost product agreement
     */
    const AVATAX_PRODUCT_LANDED_COST_AGREEMENT = 'avatax_lc_agreement';

    /**
     * Xml path to landed cost enabled
     */
    const XML_PATH_TO_AVATAX_LANDED_COST_ENABLED = 'tax/avatax_landed_cost/landed_cost_enabled';

    /**
     * Xml path to landed cost DDP countries
     */
    const XML_PATH_TO_AVATAX_LANDED_COST_DDP_COUNTRIES = 'tax/avatax_landed_cost/landed_cost_ddp_countries';

    /**
     * Xml path to landed cost DAP countries
     */
    const XML_PATH_TO_AVATAX_LANDED_COST_DAP_COUNTRIES = 'tax/avatax_landed_cost/landed_cost_dap_countries';

    /**
     * Xml path to landed cost DAP countries
     */
    const XML_PATH_TO_AVATAX_LANDED_COST_DEFAULT_UNITS_OF_WEIGHT = 'tax/avatax_landed_cost/landed_cost_units_of_weight';


    /**
     * Get if Landed Cost is Enabled
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function isLandedCostEnabled($store)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_TO_AVATAX_LANDED_COST_ENABLED, $store);
    }

    /**
     * Get Landed Cost DDP countries
     *
     * @param int|Mage_Core_Model_Store $storeId
     * @return array
     */
    public function getLandedCostDDPCountries($storeId = null)
    {
        return explode(',', Mage::getStoreConfig(self::XML_PATH_TO_AVATAX_LANDED_COST_DDP_COUNTRIES, $storeId));
    }

    /**
     * Get Landed Cost DAP countries
     *
     * @param int|Mage_Core_Model_Store $storeId
     * @return array
     */
    public function getLandedCostDAPCountries($storeId = null)
    {
        return explode(',', Mage::getStoreConfig(self::XML_PATH_TO_AVATAX_LANDED_COST_DAP_COUNTRIES, $storeId));
    }

    /**
     * Get Landed Cost Mode
     *
     * @param int|Mage_Core_Model_Store $storeId
     * @param string                    $destinationCountry
     * @return null|string
     */
    public function getLandedCostMode($storeId = null, $destinationCountry)
    {
        $mode = null;
        $originCountryCode = Mage::getStoreConfig('shipping/origin/country_id', $storeId);
        if ($this->isLandedCostEnabled($storeId) && $destinationCountry != $originCountryCode) {
            if (in_array($destinationCountry, $this->getLandedCostDDPCountries())) {
                $mode = 'DDP';
            } elseif (in_array($destinationCountry, $this->getLandedCostDAPCountries())) {
                $mode = 'DAP';
            }
        }

        return $mode;
    }

    /**
     * Get Product HTS Code
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param string $countryCode
     * @return string
     */
    public function getProductHTSCode($product, $countryCode)
    {
        $product = is_int($product) ? Mage::getModel('catalog/product')->load($product) : $product;
        $hsCode = $product->getData(self::AVATAX_PRODUCT_LANDED_COST_ATTR_HSCODE);

        /* @var OnePica_AvaTax_Model_Records_HsCode $hsCode */
        $model = Mage::getModel('avatax_records/hsCode')->load($hsCode,'hs_code');

        /* @var OnePica_AvaTax_Model_Records_HsCodeCountry $code */
        $code = $model->getCodeForCountry($countryCode);

        return $code->getId() > 0 ? $code->getHsFullCode() : null;
    }

    /**
     * Get Landed Cost Default Units Of Weight
     *
     * @param int|Mage_Core_Model_Store $storeId
     * @return string|null
     */
    public function getLandedCostDefaultUnitsOfWeight($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TO_AVATAX_LANDED_COST_DEFAULT_UNITS_OF_WEIGHT, $storeId);
    }

    /**
     * Get Product Avalara Unit Of Weight
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param string $countryCode
     * @return OnePica_AvaTax_Model_Records_UnitOfWeight
     *
     */
    public function getProductUnitOfWeight($product, $countryCode)
    {
        $result = null;

        $product = is_int($product) ? Mage::getModel('catalog/product')->load($product) : $product;

        $weight = $product->getWeight();

        if (!empty($weight) && $weight > 0) {

            $zendCode = $product->getData(self::AVATAX_PRODUCT_LANDED_COST_ATTR_UNIT_OF_WEIGHT);

            if (empty($zendCode)) {
                //get units from config settings
                $zendCode = $this->getLandedCostDefaultUnitsOfWeight($product->getStoreId());
            }

            if (!empty($zendCode)) {
                /* @var OnePica_AvaTax_Model_Records_Mysql4_UnitOfWeight_Collection $collection */
                $collection = Mage::getModel('avatax_records/unitOfWeight')
                    ->getCollection()
                    ->addFilter('zend_code', $zendCode);
                $collection->getSelect()->where('country_list REGEXP ?', $countryCode);

                /* @var OnePica_AvaTax_Model_Records_UnitOfWeight $unit */
                $unit = $collection->getFirstItem();

                $result = $unit->getId() > 0 ? $unit : null;
            }
        }

        return $result;
    }

    /**
     * Get Product HTS Code
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param string $countryCodeFrom
     * @param string $countryCodeTo
     * @return string[];
     */
    public function getProductAgreements($product, $countryCodeFrom, $countryCodeTo)
    {
        $result = array();

        $product = is_int($product) ? Mage::getModel('catalog/product')->load($product) : $product;
        $agreements = $product->getData(self::AVATAX_PRODUCT_LANDED_COST_AGREEMENT);
        if (!empty($agreements)) {
            $agreements = explode(',',$agreements);
            /* @var OnePica_AvaTax_Model_Records_Mysql4_Agreement_Collection $collection */
            $collection = Mage::getModel('avatax_records/agreement')->getCollection();
            $collection->addFieldToFilter('id', array('in' => $agreements));
            $collection->getSelect()->where('country_list REGEXP ?', $countryCodeFrom);
            $collection->getSelect()->where('country_list REGEXP ?', $countryCodeTo);
            $collection->load();

            /* @var OnePica_AvaTax_Model_Records_Agreement $agr */
            foreach ($collection as $agr) {
                array_push($result, $agr->getAvalaraAgreementCode());
            }
        }

        return $result;
    }
}
