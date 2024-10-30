<?php

/**
 * Plugin management.
 * This class defines all code necessary to run during the plugin's activation/deactivation/uninstallation.
 *
 * @package    mFloorMap
 */
class mFloorMap_Core_Installer {


    protected static $dbVersion= '1';


    /**
     * Fired on activation of plugin.
     */
    public static function Activate() {

        // validate action
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $Plugin= isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer("activate-plugin_{$Plugin}");

        // catch activation errors
        add_action('activated_plugin', array(__CLASS__,'CatchErrors'));

        // perform installation if missing record in 'wp-options' table
        $Options= mFloorMap::GetInstance()->GetOptions();
        if (!$Options) {
            self::Install();
            self::InitalContent();
        }

        // some other preparations on activation
    }


    /**
     * Fired on deactivation of plugin.
     */
    public static function Deactivate() {

        // validate action
        if (!current_user_can('activate_plugins')) {
            return;
        }
        $Plugin= isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer("deactivate-plugin_{$Plugin}");

        // catch deactivation errors
        add_action('deactivated_plugin', array(__CLASS__,'CatchErrors'));

        // some deactivating jobs
        //..

        /*temprorary*//*mFloorMap::GetInstance()->RemoveOptions();*/
    }


    /**
     * Installation will happen only during activation if database tables are not exist.
     * Called by self::Activate().
     */
    public static function Install() {

        $SQLs= array(
            'DROP TABLE IF EXISTS {pref}mfloormap_facilities',
            'CREATE TABLE {pref}mfloormap_facilities (
                `Id` int(11) NOT NULL AUTO_INCREMENT,
                `Title` varchar(40) NOT NULL,
                PRIMARY KEY (`Id`)
             ) ENGINE=InnoDB DEFAULT CHARSET={cs};',
            'DROP TABLE IF EXISTS {pref}mfloormap_floors',
            'CREATE TABLE {pref}mfloormap_floors (
                `Id` int(11) NOT NULL AUTO_INCREMENT,
                `FacId` int(11) NOT NULL,
                `Published` int(1) NOT NULL,
                `Title` varchar(255) NOT NULL,
                `Image` varchar(255) NOT NULL,
                `Ordering` int(11) NOT NULL,
                PRIMARY KEY (Id),
                KEY `Fac` (`FacId`,`Published`)
             ) ENGINE=InnoDB DEFAULT CHARSET={cs};',
            'DROP TABLE IF EXISTS {pref}mfloormap_places',
            'CREATE TABLE {pref}mfloormap_places (
                `Id` int(11) NOT NULL AUTO_INCREMENT,
                `FloId` int(11) NOT NULL,
                `Published` tinyint(1) NOT NULL,
                `Title` varchar(255) NOT NULL,
                `OfficialTitle` varchar(255) NOT NULL,
                `LocationMark` varchar(24) NOT NULL,
                `Logo` varchar(255) NOT NULL,
                `Photo` varchar(255) NOT NULL,
                `Descr` text NOT NULL,
                `ContactInfo` varchar(255) NOT NULL,
                `TimingInfo` varchar(255) NOT NULL,
                `Mapping` varchar(2000) NOT NULL,
                PRIMARY KEY (`Id`),
                KEY `ByFloor` (`FloId`,`Published`)
            ) ENGINE=InnoDB DEFAULT CHARSET={cs};',
            'DROP TABLE IF EXISTS {pref}mfloormap_placetags',
            'CREATE TABLE {pref}mfloormap_placetags (
                `PlaceId` int(11) NOT NULL,
                `TagId` int(11) NOT NULL,
                KEY `PlaceId` (`PlaceId`)
             ) ENGINE=InnoDB;',
            'DROP TABLE IF EXISTS {pref}mfloormap_tags',
            'CREATE TABLE {pref}mfloormap_tags (
                `Id` int(11) NOT NULL AUTO_INCREMENT,
                `FacId` int(11) NOT NULL,
                `Ordering` int(11) NOT NULL,
                `Title` varchar(64) NOT NULL,
                PRIMARY KEY (`Id`),
                KEY `FacId` (`FacId`)
            ) ENGINE=InnoDB DEFAULT CHARSET={cs};',
        );

        // execute these queries
        self::ExecSQL($SQLs);

        // store information about version of tables structure
        mFloorMap::GetInstance()->UpdateOption('dbVersion', self::$dbVersion);

        // store information about uninstaller callback
        // this info will be stored in database so we doing that only once, not on every request
        register_uninstall_hook(mFloorMap::GetInstance()->GetBasePluginFile(), array('mFloorMap_Core_Installer', 'Uninstall'));

        // create directories for images
        // well, not needed :)
    }


    /**
     * Fired on removing of plugin but only if it was activated ever before.
     * Plugins that are never activated had never trigger installation process
     * so uninstallation is skipped for them.
     */
    public static function Uninstall() {

        // catch uninstallation errors
        add_action('deleted_plugin', array(__CLASS__,'CatchErrors'));

        // remove all database tables
        global $wpdb;
        $Tables= array('facilities','floors','places','placetags','tags');
        foreach ($Tables as $Table) {
            $wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'mfloormap_'.$Table);
        }

        // delete record from 'wp_options' table
        mFloorMap::GetInstance()->RemoveOptions();

        // remove images
        $SubDirs= array('floors','logos','photos');
        $Uploads= wp_get_upload_dir();
        foreach($SubDirs as $SubDir) {
            array_map('unlink', glob($Uploads['basedir'].'/mfloormap/'.$SubDir.'/*'));
            rmdir($Uploads['basedir'].'/mfloormap/'.$SubDir);
        }
        rmdir($Uploads['basedir'].'/mfloormap');
    }

    /**
     * Inserts few records into empty tables.
     * Called by self::Activate().
     */
    public static function InitalContent() {

        if (is_file(__DIR__.'/demo.php')) {
            // grab content from package "demo" is present
            $SQLs= include __DIR__.'/demo.php';
        } else {
            // content for normal installation
            $SQLs= array(
            "INSERT INTO {pref}mfloormap_facilities VALUES (1, 'Default facility');",
            "INSERT INTO {pref}mfloormap_floors VALUES (1, 1, 0, 'Default floor', '', 1);",
            );
        }

        // execute these queries
        self::ExecSQL($SQLs);
    }


    /**
     * Execute supplied SQL queries,
     * placeholders {pref} and {cs} will be replaced before execution.
     *
     * @param array $Queries
     */
    protected static function ExecSQL($Queries) {

        global $wpdb;
        if (!function_exists('dbDelta')) {
            require_once trailingslashit(ABSPATH).'wp-admin/includes/upgrade.php';
        }
        // define placeholders
        $Replace= array(
            '{pref}' => $wpdb->prefix,
            '{cs}'   => 'utf8mb4',    // or to use: $wpdb->get_charset_collate() ?
        );
        // loop
        foreach ($Queries as $SQL) {
            $SQL= str_replace(array_keys($Replace), array_values($Replace), $SQL);
            dbDelta($SQL);
        }
    }


    public static function CatchErrors() {

        $Content= ob_get_contents();
        if (!$Content) {
            return;
        }
        $Content= "================================================\n"
                .date('r')."\n"
                .$Content."\n\n";
        file_put_contents(__DIR__.'/Errors.txt', $Content);
    }

}
