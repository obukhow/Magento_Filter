<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Layer category filter
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Oggetto_Filter_Model_Layer_Filter_Category extends Mage_Catalog_Model_Layer_Filter_Category
{
    /**
     * Filter values
     * @var array
     */
    protected $_filterValues;

    /**
     * Initialize filter items
     *
     * @return  Oggetto_Filter_Model_Layer_Filter_Category
     */
    protected function _initItems()
    {
        $this->_items = Mage::getSingleton('oggetto_filter/layer_filter_abstract')->_initItems($this,
            $this->_getItemsData());
        return $this;
    }

    /**
     * Create filter item object
     *
     * @param string $label    Label
     * @param mixed  $value    Value
     * @param int    $selected Selected
     * @param int    $count    Count
     *
     * @return Mage_Catalog_Model_Layer_Filter_Item
     */
    protected function _createItem($label, $value, $selected = 0, $count=0)
    {
        return Mage::getSingleton('oggetto_filter/layer_filter_abstract')->_createItem($this, $label, $value,
            $selected, $count);
    }





    /**
     * Get filter value for reset current filter state
     *
     * @return mixed
     */
    public function getResetValue($filterValue)
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        $params = $this->_filterValues;
        $currentKey = array_search($filterValue, $params);
        unset($params[$currentKey]);
        return implode($separator, $params);
    }

    /**
     * Apply category filter to layer
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Mage_Core_Block_Abstract $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Category
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();
        $this->_filterValues = $filters = explode($separator, $request->getParam($this->getRequestVar()));

        foreach ($filters as $filter) {
            if (!$filters) {
                return $this;
            }

            Mage::register('current_category_filter', $this->getCategory(), true);

            $this->_appliedCategory = Mage::getModel('catalog/category')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($filter);

            if ($this->_isValidCategory($this->_appliedCategory)) {
                $this->getLayer()->getProductCollection()
                    ->pushCategoryFilter($this->_appliedCategory);

                $this->getLayer()->getState()->addFilter(
                    $this->_createItem($this->_appliedCategory->getName(), $filter)
                );
            }
        }

        return $this;
    }


    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $key = $this->getLayer()->getStateKey().'_SUBCATEGORIES';
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
            $categoty   = $this->getCategory();
            /** @var $categoty Mage_Catalog_Model_Categeory */
            $categories = $categoty->getChildrenCategories();

            $this->getLayer()->getProductCollection()->addCountToAllCategories($categories);

            $data = array();
            foreach ($categories as $category) {
                $selected = in_array($category->getId(), $this->_filterValues);
                if ($category->getIsActive() && ($category->getProductCount() || $selected)) {
                    $data[] = array(
                        'label' => Mage::helper('core')->escapeHtml($category->getName()),
                        'value' => $category->getId(),
                        'count' => $category->getProductCount(),
                        'selected' => $selected
                    );
                }
            }
            $tags = $this->getLayer()->getStateTags();
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        return $data;
    }
}
