<?php

/**
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    mFloorMap
 * @author     Miroslav Ćurčić <office@tekod.com>
 */
class mFloorMap_Admin_Admin {

    
    // singleton instance
    protected static $Instance;
    
    // instance of main plugin object
    /* @var mFloorMap $Plugin */
    protected $Plugin;
    

    /**
     * Constructor.
     */
    public function __construct() {

        $this->Plugin= mFloorMap::GetInstance();        
    }

    
    /**
     * Return singleton instance.
     * 
     * @return self
     */
    public static function GetInstance() {

        if (!self::$Instance) {
            self::$Instance= new self;
        }
        return self::$Instance;
    }
    
    
    public function Run() {
        
        // admin page hooks
        add_action('admin_enqueue_scripts', array($this, 'OnEnqueueScripts'));
        
        // admin menu hook
        add_action('admin_menu', array($this, 'OnAdminMenu'));
    }

    /**
     * Hook listener for 'admin_enqueue_scripts' action.
     * Register the JS and CSS for the admin area.
     */
    public function OnEnqueueScripts() {

        $Ver= $this->Plugin->GetVersion();
        wp_enqueue_style(__CLASS__, plugin_dir_url(__FILE__).'CSS/mFloorMapAdmin.css', array(), $Ver, 'all' );
        wp_enqueue_script(__CLASS__, plugin_dir_url(__FILE__).'JS/mFloorMapAdmin.js', array('jquery'), $Ver, false );
    }
    
    
    /**
     * Hook listener for 'admin_menu' action.
     */
    public function OnAdminMenu() {
        
        $ClassParts= explode('_', get_class());
        $Root= reset($ClassParts);
        $Slug= strtolower($Root);
        $PageTitle= $Root;
        $MenuTitle= $Root;
        $Capability= 'manage_options';        
        $Icon= 'dashicons-location-alt';
        add_menu_page('mFloorMap places', $MenuTitle, $Capability, $Slug, null, $Icon, null);
        add_submenu_page($Slug, 'Places', 'Places', $Capability, $Slug, array($this,'AdminPagePlaces'));
        add_submenu_page($Slug, 'Facilities', 'Facilities', $Capability, $Slug.'_fa', array($this,'AdminPageFacilities'));
        add_submenu_page($Slug, 'Floors', 'Floors', $Capability, $Slug.'_fl', array($this,'AdminPageFloors'));
        add_submenu_page($Slug, 'Tags', 'Tags', $Capability, $Slug.'_ta', array($this,'AdminPageTags'));
    }

    
    public function AdminPageFacilities() {
    
        $Admin= new mFloorMap_Admin_FacilityManager();
        $Admin->Run();
    }

    public function AdminPageFloors() {
    
        $Admin= new mFloorMap_Admin_FloorManager();
        $Admin->Run();
    }    
    
    public function AdminPageTags() {
    
        $Admin= new mFloorMap_Admin_TagManager();
        $Admin->Run();
    }    
     
    public function AdminPagePlaces() {
    
        $Admin= new mFloorMap_Admin_PlaceManager();
        $Admin->Run();
    }
}

?>