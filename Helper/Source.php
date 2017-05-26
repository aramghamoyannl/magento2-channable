<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Channable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magmodules\Channable\Helper\General as GeneralHelper;
use Magmodules\Channable\Helper\Product as ProductHelper;
use Magmodules\Channable\Helper\Item as ItemHelper;
use Magmodules\Channable\Helper\Category as CategoryHelper;

class Source extends AbstractHelper
{

    const XML_PATH_LIMIT = 'magmodules_channable/general/limit';
    const XML_PATH_NAME_SOURCE = 'magmodules_channable/data/name_attribute';
    const XML_PATH_DESCRIPTION_SOURCE = 'magmodules_channable/data/description_attribute';
    const XML_PATH_BRAND_SOURCE = 'magmodules_channable/data/brand_attribute';
    const XML_PATH_EAN_SOURCE = 'magmodules_channable/data/ean_attribute';
    const XML_PATH_IMAGE_SOURCE = 'magmodules_channable/data/image';
    const XML_PATH_SKU_SOURCE = 'magmodules_channable/data/sku_attribute';
    const XML_PATH_SIZE_SOURCE = 'magmodules_channable/data/size_attribute';
    const XML_PATH_COLOR_SOURCE = 'magmodules_channable/data/color_attribute';
    const XML_PATH_MATERIAL_SOURCE = 'magmodules_channable/data/material_attribute';
    const XML_PATH_GENDER_SOURCE = 'magmodules_channable/data/gender_attribute';
    const XML_PATH_EXTRA_FIELDS = 'magmodules_channable/advanced/extra_fields';
    const XML_PATH_WEIGHT_UNIT = 'general/locale/weight_unit';
    const XML_PATH_VISBILITY = 'magmodules_channable/filter/visbility_enabled';
    const XML_PATH_VISIBILITY_OPTIONS = 'magmodules_channable/filter/visbility';
    const XML_PATH_STOCK = 'magmodules_channable/filter/stock';
    const XML_PATH_RELATIONS_ENABLED = 'magmodules_channable/advanced/relations';
    const XML_PATH_PARENT_ATTS = 'magmodules_channable/advanced/parent_atts';
    const XML_PATH_DELIVERY_TIME = 'magmodules_channable/advanced/delivery_time';
    const XML_PATH_INVENTORY = 'magmodules_channable/advanced/inventory';
    const XML_PATH_INVENTORY_DATA = 'magmodules_channable/advanced/inventory_fields';
    const XML_PATH_MANAGE_STOCK = 'cataloginventory/item_options/manage_stock';
    const XML_PATH_MIN_SALES_QTY = 'cataloginventory/item_options/min_sale_qty';
    const XML_PATH_QTY_INCREMENTS = 'cataloginventory/item_options/qty_increments';
    const XML_PATH_QTY_INC_ENABLED = 'cataloginventory/item_options/enable_qty_increments';
    const XML_PATH_CATEGORY_FILTER = 'magmodules_channable/filter/category_enabled';
    const XML_PATH_CATEGORY_FILTER_TYPE = 'magmodules_channable/filter/category_type';
    const XML_PATH_CATEGORY_IDS = 'magmodules_channable/filter/category';

    private $generalHelper;
    private $productHelper;
    private $itemHelper;
    private $categoryHelper;
    private $storeManager;

    /**
     * Source constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param General               $generalHelper
     * @param Category              $categoryHelper
     * @param Product               $productHelper
     * @param Item                  $itemHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        ItemHelper $itemHelper
    ) {
        $this->generalHelper = $generalHelper;
        $this->productHelper = $productHelper;
        $this->itemHelper = $itemHelper;
        $this->categoryHelper = $categoryHelper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param        $storeId
     * @param string $type
     *
     * @return array
     */
    public function getConfig($storeId, $type = 'feed')
    {
        $config = [];
        $config['store_id'] = $storeId;
        $config['flat'] = false;
        $config['attributes'] = $this->getAttributes($type);
        $config['price_config'] = $this->getPriceConfig($type);
        $config['filters'] = $this->getProductFilters();
        $config['inventory'] = $this->getInventoryData();

        if ($type == 'feed') {
            $config['url_type_media'] = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $config['base_url'] = $this->storeManager->getStore()->getBaseUrl();
            $config['weight_unit'] = ' ' . $this->generalHelper->getStoreValue(self::XML_PATH_WEIGHT_UNIT, $storeId);
            $config['categories'] = $this->categoryHelper->getCollection('', '', 'channable_cat_disable_export');
            $config['item_updates'] = $this->itemHelper->isEnabled();
            $config['delivery'] = $this->generalHelper->getStoreValue(self::XML_PATH_DELIVERY_TIME);
        }

        if ($type == 'api') {
            $config['api'] = $this->itemHelper->getApiConfigDetails($storeId);
        }

        return $config;
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getAttributes($type)
    {
        $attributes = [];
        $attributes['id'] = [
            'label'                     => 'id',
            'source'                    => 'entity_id',
            'parent_selection_disabled' => 1,
        ];
        $attributes['title'] = [
            'label'  => 'title',
            'source' => $this->generalHelper->getStoreValue(self::XML_PATH_NAME_SOURCE),
        ];
        $attributes['price'] = [
            'label'                     => 'price',
            'collection'                => 'price',
            'parent_selection_disabled' => 1
        ];
        $attributes['product_type'] = [
            'label'                     => 'type_id',
            'source'                    => 'type_id',
            'parent_selection_disabled' => 1,
        ];
        $attributes['status'] = [
            'label'                     => 'status',
            'source'                    => 'status',
            'parent_selection_disabled' => 1,
        ];
        $attributes['visibility'] = [
            'label'  => 'visibility',
            'source' => 'visibility',
        ];
        $attributes['manage_stock'] = [
            'label'     => 'manage_stock',
            'source'    => 'manage_stock',
            'condition' => [
                '0:false',
                '1:true',
            ],
        ];
        $attributes['availability'] = [
            'label'     => 'availability',
            'source'    => 'is_in_stock',
            'condition' => [
                '1:in stock',
                '0:out of stock'
            ]
        ];
        $attributes['qty'] = [
            'label'   => 'qty',
            'source'  => 'qty',
            'actions' => ['number'],
        ];

        if ($type != 'api') {
            $attributes['description'] = [
                'label'  => 'description',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_DESCRIPTION_SOURCE),
            ];
            $attributes['link'] = [
                'label'  => 'link',
                'source' => 'product_url',
            ];
            $attributes['image_link'] = [
                'label'  => 'image_link',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_IMAGE_SOURCE),
            ];
            $attributes['brand'] = [
                'label'  => 'brand',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_BRAND_SOURCE),
            ];
            $attributes['ean'] = [
                'label'  => 'ean',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_EAN_SOURCE),
            ];
            $attributes['sku'] = [
                'label'  => 'sku',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_SKU_SOURCE),
            ];
            $attributes['color'] = [
                'label'  => 'color',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_COLOR_SOURCE),
            ];
            $attributes['gender'] = [
                'label'  => 'gender',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_GENDER_SOURCE)
            ];
            $attributes['material'] = [
                'label'  => 'material',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_MATERIAL_SOURCE),
            ];
            $attributes['size'] = [
                'label'  => 'size',
                'source' => $this->generalHelper->getStoreValue(self::XML_PATH_SIZE_SOURCE),
            ];
            $attributes['min_sale_qty'] = [
                'label'   => 'min_sale_qty',
                'source'  => 'min_sale_qty',
                'actions' => ['number'],
                'default' => '1.00',
            ];
            $attributes['qty_increments'] = [
                'label'   => 'qty_increments',
                'source'  => 'qty_increments',
                'actions' => ['number'],
                'default' => '1.00',
            ];
            $attributes['weight'] = [
                'label'   => 'shipping_weight',
                'source'  => 'weight',
                'suffix'  => 'weight_unit',
                'actions' => ['number']
            ];
            $attributes['item_group_id'] = [
                'label'  => 'item_group_id',
                'source' => $attributes['id']['source'],
                'parent' => 2
            ];
            $attributes['is_bundle'] = [
                'label'                     => 'is_bundle',
                'source'                    => 'type_id',
                'condition'                 => [
                    '*:false',
                    'bundle:true',
                ],
                'parent_selection_disabled' => 1,
            ];
        }

        if ($extraFields = $this->getExtraFields()) {
            $attributes = array_merge($attributes, $extraFields);
        }

        $parentAttributes = $this->getParentAttributes();
        return $this->productHelper->addAttributeData($attributes, $parentAttributes);
    }

    /**
     * @return array
     */
    public function getExtraFields()
    {
        $extraFields = [];
        if ($attributes = $this->generalHelper->getStoreValue(self::XML_PATH_EXTRA_FIELDS)) {
            $attributes = @unserialize($attributes);
            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    $label = str_replace(' ', '_', $attribute['name']);
                    $extraFields[$attribute['attribute']] = [
                        'label'  => strtolower($label),
                        'source' => $attribute['attribute']
                    ];
                }
            }
        }

        return $extraFields;
    }

    /**
     * @return array|mixed
     */
    public function getParentAttributes()
    {
        $enabled = $this->generalHelper->getStoreValue(self::XML_PATH_RELATIONS_ENABLED);
        if ($enabled) {
            if ($attributes = $this->generalHelper->getStoreValue(self::XML_PATH_PARENT_ATTS)) {
                $attributes = explode(',', $attributes);
                return $attributes;
            }
        }

        return [];
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getPriceConfig($type)
    {
        $priceFields = [];
        $priceFields['price'] = 'price';
        $priceFields['final_price'] = 'price';
        $priceFields['sales_price'] = 'sale_price';
        $priceFields['sales_date_range'] = 'sale_price_effective_date';
        $priceFields['currency'] = $this->storeManager->getStore()->getCurrentCurrency()->getCode();

        if ($type != 'api') {
            $priceFields['show_currency'] = true;
        } else {
            $priceFields['show_currency'] = false;
        }

        return $priceFields;
    }

    /**
     * @return array
     */
    public function getProductFilters()
    {
        $filters = [];

        $visibilityFilter = $this->generalHelper->getStoreValue(self::XML_PATH_VISBILITY);
        if ($visibilityFilter) {
            $visibility = $this->generalHelper->getStoreValue(self::XML_PATH_VISIBILITY_OPTIONS);
            $filters['visibility'] = explode(',', $visibility);
        } else {
            $filters['visibility'] = [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ];
        }
        $relations = $this->generalHelper->getStoreValue(self::XML_PATH_RELATIONS_ENABLED);
        if ($relations) {
            $filters['relations'] = 1;
            array_push($filters['visibility'], Visibility::VISIBILITY_NOT_VISIBLE);
        } else {
            $filters['relations'] = 0;
        }

        $filters['limit'] = (int)$this->generalHelper->getStoreValue(self::XML_PATH_LIMIT);

        if ($filters['relations'] == 1) {
            $filters['exclude_parent'] = 1;
        }

        $filters['stock'] = $this->generalHelper->getStoreValue(self::XML_PATH_STOCK);

        $categoryFilter = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY_FILTER);
        if ($categoryFilter) {
            $categoryIds = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY_IDS);
            $filterType = $this->generalHelper->getStoreValue(self::XML_PATH_CATEGORY_FILTER_TYPE);
            if (!empty($categoryIds) && !empty($filterType)) {
                $filters['category_ids'] = explode(',', $categoryIds);
                $filters['category_type'] = $filterType;
            }
        }

        return $filters;
    }

    /**
     * @return array
     */
    public function getInventoryData()
    {
        $invAtt = [];
        $enabled = $this->generalHelper->getStoreValue(self::XML_PATH_INVENTORY);
        if (!$enabled) {
            return $invAtt;
        }
        if ($fields = $this->generalHelper->getStoreValue(self::XML_PATH_INVENTORY_DATA)) {
            $invAtt['attributes'] = explode(',', $fields);
            $invAtt['attributes'][] = 'is_in_stock';
            if (in_array('manage_stock', $invAtt['attributes'])) {
                $invAtt['attributes'][] = 'use_config_manage_stock';
                $invAtt['config_manage_stock'] = $this->generalHelper->getStoreValue(self::XML_PATH_MANAGE_STOCK);
            }
            if (in_array('qty_increments', $invAtt['attributes'])) {
                $invAtt['attributes'][] = 'use_config_qty_increments';
                $invAtt['attributes'][] = 'enable_qty_increments';
                $invAtt['attributes'][] = 'use_config_enable_qty_inc';
                $invAtt['config_qty_increments'] = $this->generalHelper->getStoreValue(self::XML_PATH_QTY_INCREMENTS);
                $invAtt['config_enable_qty_inc'] = $this->generalHelper->getStoreValue(self::XML_PATH_QTY_INC_ENABLED);
            }
            if (in_array('min_sale_qty', $invAtt['attributes'])) {
                $invAtt['attributes'][] = 'use_config_min_sale_qty';
                $invAtt['config_min_sale_qty'] = $this->generalHelper->getStoreValue(self::XML_PATH_MIN_SALES_QTY);
            }

            return $invAtt;
        }
        return [];
    }

    /**
     * @param $dataRow
     * @param $product
     * @param $config
     *
     * @return string
     */
    public function reformatData($dataRow, $product, $config)
    {
        if (!empty($config['categories'])) {
            if ($categoryData = $this->getCategoryData($product, $config['categories'])) {
                $dataRow = array_merge($dataRow, $categoryData);
            }
        }
        if (!empty($dataRow['image_link'])) {
            if ($imageData = $this->getImageData($dataRow)) {
                $dataRow = array_merge($dataRow, $imageData);
            }
        }
        if ($deliveryTime = $this->getDeliveryTime($dataRow, $config)) {
            $dataRow = array_merge($dataRow, $deliveryTime);
        }

        return $dataRow;
    }

    /**
     * @param $product
     * @param $categories
     *
     * @return array
     */
    public function getCategoryData($product, $categories)
    {
        $path = [];
        foreach ($product->getCategoryIds() as $catId) {
            if (!empty($categories[$catId])) {
                $category = $categories[$catId];
                if (!empty($category['path'])) {
                    $path[] = ['level' => $category['level'], 'path' => implode(' > ', $category['path'])];
                }
            }
        }
        if (!empty($path)) {
            foreach ($path as $key => $row) {
                $temp[$key] = $row['level'];
            }
            array_multisort($temp, SORT_DESC, $path);
            $data['categories'] = $path;
            return $data;
        }
        return [];
    }

    /**
     * @param $dataRow
     *
     * @return array
     */
    public function getImageData($dataRow)
    {
        $i = 0;
        $imageData = [];

        if (is_array($dataRow['image_link'])) {
            $imageLinks = $dataRow['image_link'];
            foreach ($imageLinks as $link) {
                if ($i == 0) {
                    $imageData['image_link'] = $link;
                } else {
                    $imageData['additional_imagelinks'][] = $link;
                }
                $i++;
            }
        } else {
            $imageData['image_link'] = $dataRow['image_link'];
        }

        return $imageData;
    }

    /**
     * @param $dataRow
     * @param $config
     *
     * @return array|bool
     */
    public function getDeliveryTime($dataRow, $config)
    {
        if (!empty($config['delivery'])) {
            $deliveryTime = [];
            $stock = 'in_stock';
            if (!empty($dataRow['availability'])) {
                if ($dataRow['availability'] == 'out of stock') {
                    $stock = 'out_of_stock';
                }
            }
            $countries = @unserialize($config['delivery']);
            foreach ($countries as $country) {
                if (!empty($country[$stock])) {
                    $deliveryTime['delivery_period_' . strtolower($country['code'])] = $country[$stock];
                }
            }
            return $deliveryTime;
        }

        return false;
    }
}
