<?php

/**
 * Renderer of single place, called by any shortcode.
 * 
 * @package mFloorMap
 */
class mFloorMap_Public_Shortcode_Place_Place {
        
    
    public function Render($PlaceId) {           
        
        // set same vars          
        $PluginDir= dirname(dirname(plugin_dir_url(__FILE__)));    
        $UploadsDirs= wp_get_upload_dir();
        $UploadsURL= $UploadsDirs['baseurl'].'/mfloormap';
    
        // load common model
        $Model= new mFloorMap_Public_Shortcode_Model;                        
        
        // add CSS and JS files        
        $Ver= mFloorMap::GetInstance()->GetVersion();
        wp_enqueue_style(__CLASS__, $PluginDir.'/CSS/mFloorMap.css', array(), $Ver, 'all');
        wp_enqueue_script(__CLASS__.__LINE__, $PluginDir.'/JS/mFloorMap.js', array('jquery'), $Ver, false);        
                     
        // get place data
        $Places= $Model->GetPlace($PlaceId);
        if (empty($Places)) {
            return '<b>[Place #'.$PlaceId.' not found]</b>';
        }
        $Place= reset($Places);
        
        // get tags array
        $Tags= $Model->GetPlaceTagNames($PlaceId);

        // construct backlink        
        $BackLink= mFloorMap_Public_Public::GetInstance()->GetCurrentPageURL();
            
        // prepare images locations
        $LogoSrc= ($Place['Logo']) ? $UploadsURL.'/logos/'.$Place['Logo'] : plugins_url().'/mfloormap/Public/Image/empty-logo.png';       
        $PhotoSrc= ($Place['Photo']) ? $UploadsURL.'/photos/'.$Place['Photo'] : '';
        
        // call template
        ob_start();
        include __DIR__.'/template.php';
        $HTML= ob_get_clean();        
        
        // allow plugins to customize output
        if (has_filter('mFloorMap-ShortcodePlace')) {
            $HTML= apply_filters('mFloorMap-ShortcodePlace', array(
                'HTML'=> $HTML,
                'PlaceId'=> $PlaceId,
                'Tags'=> $Tags,
                'Data'=> $Place,
            ));
        }
        
        // return rendered plugin content
        return $HTML;
    }

    
    /**
     * Return title of specified place,
     * or null if it not exist.
     * 
     * @param int $Id
     * @return string|null
     */
    public function GetPlaceTitleTag($Id) {
        
        // load common model
        $Model= new mFloorMap_Public_Shortcode_Model;
        $Places= $Model->GetPlace($Id);
        if (empty($Places)) {
            return null;
        }
        $Place= reset($Places);  
        return $Place['Title'];
    }
    
}

?>