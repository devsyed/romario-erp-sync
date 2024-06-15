<?php

defined("ABSPATH") || exit;

/** 
 * Romario ERP Importer 
 * @package Importer
 */
class Romario_ERP_Importer
{
    private static $erp_key = '7pr7y%Rzu0YrngRg6r7f';
    private static $api_url = 'https://shj.romario.online/api/ext-v1/product';

    public static function init(): void
    {
        add_action('wp_ajax_get_sync_progress', array(__CLASS__, 'get_sync_progress'));
        add_action('wp_ajax_create_job_for_importing', array(__CLASS__, 'create_job_for_importing'));
        add_filter('cron_schedules', array(__CLASS__, 'add_every_thirty_second_schedule'));

        add_action('romario_erp_sync', array(__CLASS__, 'import_products_from_erp'));

        add_action('wp_ajax_is_sync_completed', array(__CLASS__, 'is_sync_completed'));
        add_action('wp_ajax_insert_products', [__CLASS__, 'insert_products']);
    }


    public static function slugify_string($string = 'not-found')
    {
        return strtolower(str_replace([' ', '/'], ['-', '-'], $string));
    }


    public static function insert_products()
    {
        $option_key = 'erp_products_inserted';
        $batch_size = 100; // Adjust this value as needed
        $products_inserted = get_option($option_key, 0);

        $get_downloaded_products = self::get_option_values_with_wildcard('products_batch');

        $products = array_merge(...array_map(function($option) {
            return maybe_unserialize($option->option_value);
        }, $get_downloaded_products));

        
        $batches = array_chunk($products, $batch_size);

        $current_batch_index = floor($products_inserted / $batch_size);
        $current_batch = $batches[$current_batch_index] ?? [];

        if (empty($current_batch)) {
            wp_send_json_success(['insertion_complete' => true]);
        }

        Romario_Products::insert_products($current_batch);

        $products_inserted += count($current_batch);
        update_option($option_key, $products_inserted);

        wp_send_json_success(['insertion_complete' => false, 'products_inserted' => $products_inserted]);
    }


    public static function is_sync_completed()
    {
        $sync_completed = get_option('sync_completed_without_errors', false);
        $total_pages_synced = get_option('erp_page_synced_count', false);
        if ($sync_completed) {
            wp_send_json_success(["completed" => true, 'total_products_synced' => $total_pages_synced * 100]);
        }
        wp_send_json_success(["completed" => false, 'total_products_synced' => $total_pages_synced * 100]);
    }


    public static function create_job_for_importing()
    {
        if (!wp_next_scheduled('romario_erp_sync')) {
            wp_schedule_event(time(), 'every_minute', 'romario_erp_sync');
        }
    }

    public static function add_every_thirty_second_schedule($schedules)
    {
        $schedules['every_thirty_seconds'] = array(
            'interval' => 30,
            'display'  => __('Every 30 Second'),
        );
        return $schedules;
    }

    public static function get_option_values_with_wildcard($option_name)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'options';
        $option_pattern = '%' . $wpdb->esc_like($option_name) . '%';
        $sql = $wpdb->prepare("SELECT option_value FROM $table_name WHERE option_name LIKE %s", $option_pattern);
        $results = $wpdb->get_results($sql, OBJECT);
        return $results;
    }

    public static function import_products_from_erp()
    {
        if (get_option('sync_completed_without_errors', false)) {
            return;
        }

        try {
            $option_key = 'erp_page_synced_count';
            $totalPageSynced = get_option($option_key, 0);
            $maxPageCount = 5; // Maximum number of pages to fetch

            for ($count = 0; $count < $maxPageCount; $count++) {
                $pageNo = $totalPageSynced + 1 + $count;

                $response = wp_remote_post(self::$api_url, array(
                    'headers' => array(
                        'Accept'              => 'application/json',
                        'robotic-erp-api-key' => self::$erp_key,
                        'Content-Type'        => 'application/json',
                    ),
                    'body'    => json_encode(array(
                        'pageSize' => 'SIZE_100',
                        'pageNo'   => $pageNo,
                    )),
                    'timeout' => 60,
                ));

                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    file_put_contents(ROMARIO_PLUGIN_PATH . 'api_logs.txt', $response->get_error_message(), FILE_APPEND);
                    return new WP_Error('api_error', 'Failed to fetch data');
                }

                $data = json_decode(wp_remote_retrieve_body($response), true);
                $hasMore = $data['hasMore'];

                update_option($option_key, $pageNo);
                update_option('products_batch_' . $pageNo, $data['items']);

                if (!$hasMore) {
                    update_option('sync_completed_without_errors', true);
                    wp_clear_scheduled_hook('romario_erp_sync');
                    break;
                }
            }
        } catch (\Exception $err) {
            error_log('Ran into an error: ' . $err->getMessage(), 3, plugin_dir_path(__FILE__) . 'error.log');
            return $err->getMessage();
        }
    }

}

Romario_ERP_Importer::init();
