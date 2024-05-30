<?php
defined("ABSPATH") || exit;

class Romario_BUI_Importer
{
    public static function init()
    {
        add_action('wp_ajax_upload_bulk_images', [__CLASS__, 'upload_bulk_images']);
    }

    public static function upload_bulk_images() {
        $images = $_POST['imageNames'];
        $products = self::get_products_by_partial_sku($images);
    
        wp_send_json($products);
    }

    public static function get_all_product_ids_with_refNo()
    {
        $args = array(
            'post_type' => 'product_variation',
            'posts_per_page' => -1, 
            'status' => 'publish'
        );
        $query = new WP_Query($args);
        $products = [];
        foreach($query->get_posts() as $variation){
            if(get_post_meta($variation->ID, 'refNo', true) == '' || get_post_meta($variation->ID, 'refNo', true) == null ) continue;
            $products[$variation->ID] = get_post_meta($variation->ID, 'refNo', true); 
        }
        return $products;
    }
    

    public static function get_products_by_partial_sku($images) {
        $matching_products = array();
        try {
            $all_products = self::get_all_product_ids_with_refNo();

            foreach ($images as $image) {
                $slugged_image_name = strtolower(str_replace([' ', '/'], ['-', '-'], $image['name']));
                
                foreach ($all_products as $id => $slug) {
                    // Check if the slugged image name contains the product slug
                    if (strpos($slugged_image_name, $slug) !== false) {
                        $vp = new WC_Product_Variation($id);
                        $vp->set_image_id($image['id']);
                        $parent = wp_get_post_parent_id($id);
                        $image_ids = get_post_meta($parent,'_product_image_gallery',true);
                        $all_ids = array_merge(array($image['id']),[$image_ids]);
                        $exploded_ids = implode(',', $all_ids);
                        update_post_meta($parent,'_thumbnail_id', $all_ids[0]);
                        update_post_meta($parent,'_product_image_gallery',$exploded_ids);
                        $vp->save();
                    }
                }
            }
        } catch (Throwable $th) {
            wp_send_json_error($th->getMessage());
        }

        return $matching_products;

    }
    
    
}


Romario_BUI_Importer::init();