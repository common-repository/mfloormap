<?php

/**
 * Model for all shortcodes.
 * 
 * @package mFloorMap
 */

class mFloorMap_Public_Shortcode_Model {

    
    public function GetPlacesFromFloor($FloorId) {
        
        global $wpdb;
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_places WHERE Published=1 AND FloId='.intval($FloorId);
        return $wpdb->get_results($SQL, ARRAY_A);        
    }
    
    
    public function GetPlacesFromFacility($FacId) {
        
        global $wpdb;
        $SQL= 'SELECT T1.*, T2.Title AS FloorTitle'
            .' FROM '.$wpdb->prefix.'mfloormap_places AS T1'
            .' LEFT JOIN '.$wpdb->prefix.'mfloormap_floors AS T2 ON T2.Id=T1.FloId'
            .' WHERE T1.Published=1 AND T2.FacId='.intval($FacId);
        return $wpdb->get_results($SQL, ARRAY_A);        
    }
    
    
    public function GetFloor($FloorId) {
        
        global $wpdb;
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_floors '
           .'WHERE Published=1 AND Id='.intval($FloorId);
        return $wpdb->get_results($SQL, ARRAY_A);
    }
    
    
    public function GetFloors($FacilityId) {
        
        global $wpdb;
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_floors '
           .'WHERE Published=1 AND FacId='.intval($FacilityId).' ORDER BY Ordering';
        return $wpdb->get_results($SQL, ARRAY_A);
    }
    
    
    public function GetTags($FacilityId) {
        
        global $wpdb;
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_tags '
           .'WHERE FacId='.intval($FacilityId).' ORDER BY Ordering';
        return $wpdb->get_results($SQL, ARRAY_A);
    }
   
    
    public function GetFacilityFromFloor($FloorId) {
        
        global $wpdb;
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_floors WHERE Id='.intval($FloorId);
        return $wpdb->get_results($SQL, ARRAY_A);
    }
    
    
    
    public function GetPlace($PlaceId) {
        
        global $wpdb;
        $SQL= 'SELECT a.*, b.Title AS FloTitle'
            .' FROM '.$wpdb->prefix.'mfloormap_places AS a'
            .' LEFT JOIN '.$wpdb->prefix.'mfloormap_floors AS b ON a.FloId=b.Id'
            .' WHERE a.Published=1 AND b.Published=1 AND a.Id='.intval($PlaceId);
        return $wpdb->get_results($SQL, ARRAY_A);
    }
    
    
    public function GetPlaceTagNames($PlaceId) {
        
        global $wpdb;
        $SQL= 'SELECT t.Title'
           .' FROM '.$wpdb->prefix.'mfloormap_placetags as pt'
           .' LEFT JOIN '.$wpdb->prefix.'mfloormap_tags as t ON pt.TagId=t.Id'
           .' WHERE pt.PlaceId='.intval($PlaceId).' ORDER BY t.Ordering';
        $List= $wpdb->get_results($SQL, ARRAY_A);      
        $Result= array();
        foreach($List as $Item) {$Result[]= $Item['Title'];}
        return $Result;
    }
    
        
    /*public function GetFloorBackLink($PlaceData) {        
                
        global $wpdb;
        $Fac= intval($PlaceData['FacId']);
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_floors '
           .'WHERE Published=1 AND FacId='.$Fac.' ORDER BY Ordering';
        $Floors= $wpdb->get_results($SQL, ARRAY_A); 
        foreach($Floors as $key=>$val) {
            if ($val['Id']<>$PlaceData['FloId']) {continue;}
            return 'index.php?option=com_mfloormap&view=map&f='.$key;
        }
        return 'javascript:history.back()';
    }*/
    
        
}