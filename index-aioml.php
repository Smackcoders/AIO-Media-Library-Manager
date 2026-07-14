<?php
/**
 * AIO Media Library Manager.
 *
 * AIO Media Library Manager plugin file.
 *
 * @package   Smackcoders\AIOMLM
 * @copyright Copyright (C) 2010-2025, Smackcoders Inc - info@smackcoders.com
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: AIO Media Library Manager
 * Version:     1.0.0
 * Plugin URI:  https://www.smackcoders.com
 * Description: Organize your media mess! Use Folders, Drag & Drop for WordPress. Download AIO Media Library Manager.
 * Author:      Smackcoders
 * Author URI:  https://www.smackcoders.com/wordpress.html
 * Text Domain: aio-media-library-manager
 * Domain Path: /languages
 * License:     GPLv2
 */

namespace Smackcoders\Aioml;

if (!defined('ABSPATH')) {
    exit; 
}
define('AIOML_DIR',__DIR__);

require_once AIOML_DIR.'/installation/aioml-install.php';
require_once AIOML_DIR.'/connector/aioml-connector.php';
register_activation_hook(__FILE__,[AiomlInstallation::getInstance(),'Install']);
AdminMenu::getInstance();

