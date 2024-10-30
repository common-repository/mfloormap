<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    mFloorMap
 * @author     Miroslav Ćurčić <office@tekod.com>
 */
class mFloorMap {

    
    // path to first loaded plugin file
    protected $BasePluginFile;

    // the unique identifier of this plugin
    protected $PluginId;

    // current version of the plugin
    protected $Version;

    // identifier of row in 'wp_options' table.
    protected $OptionId= 'mFloorMap';

    // singleton instance
    protected static $Instance;
    
    // instance of "admin" object
    /* @var mFloorMap_Admin_Admin $Admin */
    protected $Admin;
    
    // instance of "public" object
    /* @var mFloorMap_Public_Public $Public */
    protected $Public;



    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function __construct($ContructorOptions) {

        $this->PluginId= strtolower(get_class());    // convention: plugin's identifier is strlower form of classname
        $this->Version= $ContructorOptions['Version'];
        $this->BasePluginFile= $ContructorOptions['BasePluginFile'];        
    }

    
    /**
     * Static class constructor.
     * 
     * @param array $ContructorOptions
     */
    public static function Init($ContructorOptions) {
        
        self::$Instance= new static($ContructorOptions);
        self::$Instance->Run();
    }

    
    /**
     * Get singleton instance of this class.
     * 
     * @return self
     */
    public static function GetInstance() {
        
        return self::$Instance;
    }
    

    /**
     * Register internal class autoloader.
     */
    private function SetupAutoloader() {
        
        spl_autoload_register(array($this, 'Autoloader'));
    }


    /**
     * Autoloader callable.
     */
    public function Autoloader($Class) {  
        
        // using simple PEAR-like strategy
        $Parts= explode('_', $Class);
        $RootName= array_shift($Parts);   
        if ($RootName <> 'mFloorMap') {
            return false;                // skip other classes
        }
        // note that first part of classname is striped off
        $Path= implode(DIRECTORY_SEPARATOR, $Parts).'.php';    
        require_once dirname(__DIR__).'/'.$Path;                        
        return true;
    }

    
    /**
     * Hook listener for 'plugins_loaded' action.
     */
    public function OnAllPluginsLoaded() {
    
        // init multilanguage support
        $this->SetupIntl();
    }

    
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function SetupHooks() {

        // execute this after loading of all plugins
        add_action('plugins_loaded', array($this, 'OnAllPluginsLoaded'));
        
        // plugin management hooks
        register_activation_hook(  $this->BasePluginFile, array('mFloorMap_Core_Installer', 'Activate'));
        register_deactivation_hook($this->BasePluginFile, array('mFloorMap_Core_Installer', 'Deactivate'));
    }

    /**
     * Initialize i18n.
     */
    protected function SetupIntl() {
        
        load_plugin_textdomain('mFloorMap', false, $this->PluginId.'/Lang/');
    }
    

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function Run() {
        
        // setup common functionalities
        $this->SetupAutoloader();
        $this->SetupHooks();
        $this->SetupIntl();
        
        // delegate initialization of admin and public functionalities to their own subsystems
        $this->Admin= mFloorMap_Admin_Admin::GetInstance();
        $this->Admin->Run();
        $this->Public= mFloorMap_Public_Public::GetInstance();
        $this->Public->Run();                
    }


    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string
     */
    public function GetPluginId() {

        return $this->PluginId;
    }

    
    /**
     * Return path to first loaded file of this plugin.
     * 
     * @return string
     */
    public function GetBasePluginFile() {

        return $this->BasePluginFile;
    }


    /**
     * Retrieve the version number of the plugin.
     *
     * @return string
     */
    public function GetVersion() {

        return $this->Version;
    }
    

    /**
     * Getters and setters for option/options
     */
    public function GetOption($Name, $DefaultValue) { 
        $Options= get_option($this->OptionId);
        if (!$Options) {
            $Options= array();
        }
        return isset($Options[$Name]) ? $Options[$Name] : $DefaultValue;
    }
    
    
    public function GetOptions() {    
        $Options= get_option($this->OptionId);
        return !$Options ? false : $Options;
    }
    
    
    public function UpdateOption($Name, $Value) {                        
        $Options= get_option($this->OptionId);
        if (!$Options) {
            $Options= array();
        }
        $Options[$Name]= $Value;
        update_option($this->OptionId, $Options, false);
    }     
    
    
    public function UpdateOptions(array $Items) {                        
        $Options= get_option($this->OptionId);
        if (!$Options) {
            $Options= array();
        }
        $Options= $Items + $Options;
        update_option($this->OptionId, $Options, false);
    }
    
    
    public function RemoveOptions() {
        delete_option($this->OptionId);
    }
    
}

?>