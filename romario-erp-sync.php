<?php

/** 
 * Plugin Name: Romario ERP Sync
 * Description: Syncs Romario ERP 
 * Author: DevSyed
 */

defined('ABSPATH') || exit;

final class Romario_ERP_Sync
{
    private static $instance;

    public static function get_instance()
    {
        if (!isset(self::$instance) && !(self::$instance instanceof Romario_ERP_Sync)) {
            self::$instance = new Romario_ERP_Sync;
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->setup_constants();
        $this->include_required_files();
        $this->enqueue_style_scripts();
    }

    private function setup_constants()
    {
        define('ROMARIO_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('ROMARIO_PUBLIC_ASSETS', plugin_dir_url(__FILE__) . 'assets');
    }

    private function include_required_files()
    {
        require_once ROMARIO_PLUGIN_PATH . '/includes/class-romario-menu.php';
        require_once ROMARIO_PLUGIN_PATH . '/includes/class-romario-products.php';
        require_once ROMARIO_PLUGIN_PATH . '/includes/class-romario-erp-importer.php';
        require_once ROMARIO_PLUGIN_PATH . '/includes/class-adcb-payment-gateway.php';
        require_once ROMARIO_PLUGIN_PATH . '/includes/class-bulk-upload-images.php';
    }


    public function enqueue_style_scripts()
    {
        wp_enqueue_script('romario-importer-js', ROMARIO_PUBLIC_ASSETS . '/importer.js', array('jquery'), time(), true);
        wp_enqueue_style('romario-style-css', ROMARIO_PUBLIC_ASSETS . '/style.css', array(), '1.0', 'all');
        wp_localize_script('romario-importer-js', 'ajax_handler', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));

        add_filter('woocommerce_account_menu_items', function ($tabs) {
            unset($tabs['downloads']);
            return $tabs;
        });

        
    }
}

Romario_ERP_Sync::get_instance();
