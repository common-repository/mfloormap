<?php

/**
 * Plugin Name:       mFloorMap
 * Plugin URI:        www.tekod.com/demo/wordpress/mfloormap
 * Description:       mFloorMap is a plugin for sites that need to display floor plans for shopping centers, malls, parking lots, marketplaces...
 * Version:           1.0.1
 * Author:            Tekod labs.
 * Author URI:        www.tekod.com
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mFloorMap
 * Domain Path:       /Lang
 */

// If this file is called directly, abort.
defined('WPINC') or die('No direct access!');


// prepare core plugin constructor options
$PluginOptions= array(

    // plugin version, same value as version from comment block from above
    'Version'=> '1.0.1',

    // reference to this file will be needed for some wp functionsy
    'BasePluginFile'=> __FILE__,
);


// load and execute core plugin class
require __DIR__.'/Core/mFloorMap.php';
mFloorMap::Init($PluginOptions);

?>