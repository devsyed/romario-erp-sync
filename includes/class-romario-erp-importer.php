<?php
defined("ABSPATH") || exit;

class Romario_ERP_Importer
{
    private static $erp_key = '7pr7y%Rzu0YrngRg6r7f';

    public static function init()
    {
        add_action('wp_ajax_import_products_from_erp', array(__CLASS__, 'import_products_from_erp'));
    }

    public static function create_category($category_slug, $category_name)
    {
        $term = term_exists($category_name, 'product_cat');

        if ($term !== 0 && $term !== null) {
            return $term['term_id'];
        }

        $category_args = array(
            'description' => 'Category Description',
            'slug'        => $category_slug,
            'parent'      => 0,
        );

        $category_id = wp_insert_term($category_name, 'product_cat', $category_args);

        if (is_wp_error($category_id)) {
            error_log('Failed to create category: ' . $category_id->get_error_message());
            return false;
        }

        return $category_id['term_id'];
    }

    public static function create_attribute($attribute_name, $attribute_slug, $type = 'select')
    {

        $taxonomy = 'pa_' . $attribute_slug;
        $tax_exists = taxonomy_exists($taxonomy);

        if (!$tax_exists) {
            $attribute = wc_create_attribute(array(
                'name'         => $attribute_name,
                'slug'         => $attribute_slug,
                'type'         => $type,
                'order_by'     => 'menu_order',
                'has_archives' => true,
            ));

            if (is_wp_error($attribute)) {
                error_log('Failed to create attribute: ' . $attribute->get_error_message());
            }
        } else {
            $attribute = get_taxonomy('pa_' . $attribute_slug)->ID;
        }
        return $attribute;
    }

    public static function create_brand($brand_slug, $brand_name)
    {
        $term = term_exists($brand_name, 'cbh_brand');

        if ($term !== 0 && $term !== null) {
            return $term['term_id'];
        }

        $category_args = array(
            'description' => 'Category Description',
            'slug'        => $brand_slug,
            'parent'      => 0,
        );

        $brand_id = wp_insert_term($brand_name, 'cbh_brand', $category_args);

        if (is_wp_error($brand_id)) {
            error_log('Failed to create brand: ' . $brand_id->get_error_message());
            return false;
        }

        return $brand_id['term_id'];
    }

    public static function slugify_string($string = 'not-found')
    {
        return strtolower(str_replace([' ', '/'], ['-', '-'], $string));
    }

    public static function create_attribute_options($attribute_slug, $options)
    {
        $tax = get_taxonomy('pa_' . $attribute_slug)->ID;
        if ($tax) {
            foreach ($options as $option) {
                $term = term_exists($option, $attribute_slug);

                if (!$term || $term == 0 || $term == null) {
                    $term_data = wp_insert_term($option, $attribute_slug);

                    if (is_wp_error($term_data)) {
                        error_log('Failed to create attribute option: ' . $term_data->get_error_message());
                    }
                }
            }
        }
    }

    public static function create_tags($tag_names)
    {
        $tag_ids = array();

        foreach ($tag_names as $tag_name) {
            $tag = term_exists($tag_name, 'product_tag');

            if (!$tag || $tag == 0 || $tag == null) {
                $tag_data = wp_insert_term($tag_name, 'product_tag');

                if (is_wp_error($tag_data)) {
                    error_log('Failed to create tag: ' . $tag_data->get_error_message());
                } else {
                    $tag_ids[] = $tag_data['term_id'];
                }
            } else {
                $tag_ids[] = $tag['term_id'];
            }
        }

        return $tag_ids;
    }

    public static function fetch_products_from_erp()
    {
        try {
            $products = [];
            $page = 0;

            do {
                $response = wp_remote_post('https://web.romario.online/api/ext-v1/product', array(
                    'headers' => array(
                        'Accept'              => 'application/json',
                        'robotic-erp-api-key' => self::$erp_key,
                        'Content-Type'        => 'application/json',
                    ),
                    'body'    => json_encode(array(
                        'pageSize' => 'SIZE_100',
                        'pageNo'   => 0,
                    )),
                ));
                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    return new WP_Error('api_error', 'Failed to fetch data');
                }

                $data = json_decode(wp_remote_retrieve_body($response), true);
                $hasMore = $data['hasMore'];
                $products = array_merge($products, $data['items']);
                $page++;
            } while ($hasMore);
            return $products;
        } catch (Exception $err) {
            error_log('Ran into an error: ' . $err->getMessage());
            return $err->getMessage();
        }
    }

    public static function create_term($term, $attribute)
    {
        $term_exists = term_exists($attribute, 'pa_' . $term);

        if ($term_exists !== 0 && $term_exists !== null) {
            $term_create = wp_insert_term($attribute, 'pa_' . $term);

            if (is_wp_error($term_create)) {
                return $term_create->get_error_message();
            }
            return $term_create['term_id'];
        }

        return $term_exists['term_id'];
    }

    public static function get_all_attributes()
    {
    }

    public static function import_products_from_erp()
    {
        $size_att = self::create_attribute('Size', 'size', 'button');
        $color_att = self::create_attribute('Color', 'color', 'color');

        $products = self::fetch_products_from_erp();

        if (is_wp_error($products)) {
            wp_send_json_error(array('message' => $products->get_error_message()));
        }

        if (!$products) {
            wp_send_json_error(array('message' => 'No Products found on the ERP'));
        }

        $products_created = [];
try {
    foreach ($products as $product) {
        $brand = self::slugify_string($product['brand']);
        $cat = self::slugify_string($product['category']);
        $tag = self::slugify_string($product['department']);

        $create_brand = self::create_brand($brand, $product['brand']);
        $create_category = self::create_category($cat, $product['category']);
        $create_tag = self::create_tags([$tag]);

        $slug = self::slugify_string($product['products'][0]['description']);
        $existing_product = self::get_product_by_slug($slug);

        if ($existing_product) {
            // Update existing product
            $vp = new WC_Product_Variable($existing_product->get_id());
        } else {
            // Create new product
            $vp = new WC_Product_Variable();
            $vp->set_name($product['products'][0]['displayName']);
            $vp->set_slug($slug);
        }

        // Set attributes
        $attribute_size = new WC_Product_Attribute();
        $attribute_size->set_name('Size');
        $attribute_size->set_options(array_unique(array_column($product['products'], 'size')));
        $attribute_size->set_position(0);
        $attribute_size->set_visible(true);
        $attribute_size->set_variation(true);

        $attribute_color = new WC_Product_Attribute();
        $attribute_color->set_name('Color');
        $attribute_color->set_options(array_unique(array_column($product['products'], 'color')));
        $attribute_color->set_position(1);
        $attribute_color->set_visible(true);
        $attribute_color->set_variation(true);

        $vp->set_attributes(array($attribute_size, $attribute_color));

        // Save or update product
        $vp_id = $vp->save();

        // Save variations
        foreach ($product['products'] as $variation_p) {
            $variation = new WC_Product_Variation($variation_p['id']); // if the variation exists, update it
            $variation->set_parent_id($vp_id);
            $variation->set_attributes(array(
                'size' => $variation_p['size'],
                'color' => $variation_p['color'],
            ));
            $variation->set_regular_price($variation_p['price']['currentAmount']);
            $variation->save();

            update_post_meta($variation->get_id(), 'refNo', self::slugify_string($product['id'] . '_' . $variation_p['color']));
        }

        $products_created[] = $vp_id; // Store the product ID
    }
} catch (Throwable $th) {
    wp_send_json_error(array('message' => $th->getMessage()));
}

        update_option('romario_last_synced', date('d/m/Y'));
        return $products_created;
    }

    public static function get_product_by_slug($slug)
    {
        $args = array(
            'post_type' => 'product',
            'name' => $slug,
            'numberposts' => 1,
        );

        $products = get_posts($args);

        if (!empty($products)) {
            return wc_get_product($products[0]->ID);
        }

        return null;
    }
}

Romario_ERP_Importer::init();
