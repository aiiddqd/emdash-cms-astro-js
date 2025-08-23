<?php 

namespace ProcessFlows;

// Trait for WooCommerce Products
trait ProductTrait {

    public static function getProductAttributes($product_id) {
        $product = wc_get_product($product_id);
        return $product ? $product->get_attributes() : [];
    }

    public static function setProductAttributes($product_id, $attribute_ids, $taxonomy) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }
        //if $taxonomy contains 'pa_' prefix
        if (strpos($taxonomy, 'pa_') === 0) {
            $name_attribute = substr($taxonomy, 3);
        } else {
            $name_attribute = $taxonomy;
            $taxonomy = 'pa_' . $name_attribute;
        }

        if ($product) {
            $attributes = $product->get_attributes();
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id(wc_attribute_taxonomy_id_by_name($name_attribute));
            $attribute->set_name($taxonomy);
            $attribute->set_options($attribute_ids);
            // $attribute->set_position(0);
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $attributes[$taxonomy] = $attribute;
            $product->set_attributes($attributes);
            return $product->save();
        }
    }

}