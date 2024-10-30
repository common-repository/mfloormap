<?php

/**
 * Shortcode "Facility".
 * 
 * @package mFloorMap
 */

class mFloorMap_Public_Shortcode_Facility_Facility {
    
       
    public function Render($FloorId) { 
        
        // set same vars
        $PluginDir= dirname(dirname(plugin_dir_url(__FILE__)));    
        $PluginURL= plugins_url( 'Public', dirname(dirname(__DIR__)));
        $Uploads= wp_get_upload_dir();
        $UploadsURL= $Uploads['baseurl'].'/mfloormap';
                          
        $Model= new mFloorMap_Public_Shortcode_Model;        
                
        // add CSS and JS files        
        $Ver= mFloorMap::GetInstance()->GetVersion();
        wp_enqueue_style(__CLASS__, $PluginDir.'/CSS/mFloorMap.css', array(), $Ver, 'all');
        wp_enqueue_script(__CLASS__.__LINE__, $PluginDir.'/JS/mFloorMap.js', array('jquery'), $Ver, false);        
        wp_enqueue_script(__CLASS__.__LINE__, $PluginDir.'/JS/jquery.imagemapster.js', array('jquery'), $Ver, false);
                        
        // get facility
        $FacilityId= $Model->GetFacilityFromFloor($FloorId);

        // get details about current floor
        $FloorsData= $Model->GetFloors($FacilityId);
        if (empty($FloorsData)) {
            return '<b>[Floors not found]</b>';
        }
        $Floors= array();
        foreach($FloorsData as $FloorData) {
            $Floors[$FloorData['Id']]= array(
                'Id'    => $FloorData['Id'],
                'ImgSrc'=> $UploadsURL.'/floors/'.urlencode($FloorData['Image']),
                'Areas' => '', 
            );
        }

        // get list of places in this facility
        $Places= $Model->GetPlacesFromFacility($FacilityId);

        // get list of all tags for searchbytags
        $FacTags= array();
        foreach($Model->GetTags($FacilityId) as $t) {
            $FacTags[$t['Id']]= esc_html($t['Title']);
        }
        
        // get colors from configuration
        $Colors= mFloorMap_Public_Public::GetColors();

        // render map area and data-array
        $DataArray= array();
        foreach($Places as $Place) {
            $Coords= str_replace(array('"',"'",' '), '', $Place['Mapping']);
            if (!$Coords) {
                $Coords= '0,0'; // must be something because of highlighter
            }
            $Key=  'K'.$Place['Id'];
            $Href= '?mFloorMapPlace='.mFloorMap_Public_Public::Transliterate($Place['Title']).'-'.$Place['Id'];
            $Title= esc_js(esc_html($Place['Title']));
            $Loc1=  esc_js(esc_html($Place['LocationMark']));
            $Loc2=  esc_js(esc_html($Place['FloorTitle']));  
            $Logo=  esc_js(esc_html($Place['Logo']));                  
            $Tel=   esc_js(esc_html($Place['ContactInfo']));
            $Time=  esc_js(esc_html($Place['TimingInfo']));
            $Tags= array_map('esc_html', $Model->GetPlaceTagNames($Place['Id']));
            $Tags= empty($Tags) ? "" : "'".implode("','",$Tags)."'";
            $FloId= $Place['FloId'];
            
            $Floors[$FloId]['Areas'] .= '
              <area data="'.$Key.'" href="'.$Href.'" shape="poly" coords="'.$Coords.'">';
            $DataArray[]= "$Key:['$Title','$Loc1','$Loc2','$Logo','$Tel','$Time',[$Tags],$FloId]";
        }          
        
        // call template
        ob_start();
        include __DIR__.'/template.php';
        $HTML= ob_get_clean();        
        
        // allow plugins to customize output
        if (has_filter('mFloorMap-ShortcodeFacility')) {
            $HTML= apply_filters('mFloorMap-ShortcodeFacility', array(
                'HTML'   => $HTML,
                'FacId'  => $FacilityId,
                'Floors' => $Floors,
                'Places' => $Places,
                'Tags'   => $FacTags,
                'Data'   => $DataArray,
                'Colors' => $Colors,
            ));
        }
        
        // return rendered plugin content
        return $HTML;
    }

   
    
}

?>