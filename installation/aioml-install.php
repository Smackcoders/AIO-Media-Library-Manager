<?php
namespace Smackcoders\Aioml;
class AiomlInstallation{
     
    private static $instance = null;
    private function __construct(){}

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function Install(){
        global $wpdb;
    
        $aioml_table_name = sanitize_key($wpdb->prefix . 'aio_media_library');
        $aioml_charset_collate = $wpdb->get_charset_collate();
    
        $aioml_sql = "CREATE TABLE $aioml_table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            term_id INT(11) NOT NULL,
            name VARCHAR(250) NOT NULL,
            slug VARCHAR(250) NOT NULL,
            parent INT(11) NOT NULL DEFAULT 0,
            count INT(11) NOT NULL DEFAULT 0,
            created_by INT(11) NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY id (id)
        ) $aioml_charset_collate;";
    
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($aioml_sql);
    
    }

    public function Uninstall(){

    }
}
