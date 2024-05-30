<?php 

defined('ABSPATH') || exit; 

class Romario_Menu
{
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__,'romario_menu_page'));
    }

    public static function romario_menu_page()
    {
        add_menu_page(
            'Romario Page',  
            'Romario',        
            'manage_options', 
            'romario_page',  
            array(__CLASS__,'romario_page_content'), 
            'dashicons-admin-plugins', 
            1
        );
    }

    
    public static function romario_page_content()
    {
        require_once ROMARIO_PLUGIN_PATH . '/templates/menu-page-template.php';
    }
}


Romario_Menu::init();