<?php

/**
 * Administration og facilities (groups of floors).
 * 
 * @package mFloorMap
 */
class mFloorMap_Admin_FacilityManager extends mFloorMap_Admin_BaseManager {

    
    protected $MenuSlug= 'mfloormap_fa';

    protected $Table= 'mfloormap_facilities';
    
    protected $ShortCode= 'mfloormap-facility';
    
    protected $TDPrefix= 'fa.';
    
    protected $BulkActions= array(
        'Delete' => '__Delete',
    );   

    protected $Columns=  array(
        'Headers'=> array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'Id' => 'ID',
            'Title' => '__Title'/* def */,
            'Shortcode' => '__Shortcode'/* def */,
        ),
        'Sortable'=> array(
            'Id'=> array('Id', false),
            'Title' => array('Title', false),
        ),
        'Hidden'=> array(            
        ),
    );

      
    protected function LoadItemData() {
        
        // load from database        
        if ($this->Id > 0) {
            global $wpdb;        
            $SQL= 'SELECT * FROM '.$this->Table.' WHERE '.$this->PrimaryKey.'='.$this->Id.' LIMIT 1';
            $Results= $wpdb->get_results($SQL, ARRAY_A);
            $R= empty($Results) ? null : $Results[0];
        } else {
            $R= null;
        }
        
        // prepare item record
        $this->ItemData= array(
            //  key          value from database       value for new record    
            'Title'    => $R ? $R['Title']             : '',
        );
    }
    
    
    /**
     * Render form controls (for existing and new items).
     */
    protected function RenderFormRows() {  
       
        return '
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_title">'.esc_html__('Name').'</label>
                </th>
                <td>
                    <input type="text" name="title" id="item_title" value="'.esc_attr($this->ItemData['Title']).'" size="40" maxlength="250" autocomplete="off" />
                </td>
            </tr>';
    }
    
    
    
    protected function CanDelete($Id) {
    
        // dont allow deletion if there are assigned floors
        global $wpdb;
        $Count= $wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'mfloormap_floors WHERE FacId='.intval($Id));
        if ($Count === '0') {
            return true;
        }
        $Name= $wpdb->get_var('SELECT Title FROM '.$this->Table.' WHERE Id='.intval($Id));
        $this->NotificationError(sprintf(esc_html__('Error.CannotDelete.HasFloors','mFloorMap'), $Name, $Count));
        return false;
    }
    
    
    
    //------------------------------------------------------------------------
    //                              Actions
    // -----------------------------------------------------------------------
    
    
    /**
     * Action: modify data.
     */
    protected function Action_Update() {
        
        global $wpdb;
        
        // get para
        $Id   = $this->Input('id', 'I');
        $Title= $this->Input('title', 'T');
             
        // storing
        $SQL= $Id === 0
            ? "INSERT INTO $this->Table VALUES(0, '%s')"
            : "UPDATE $this->Table SET Title='%s' WHERE $this->PrimaryKey=".intval($Id);
        $Result= $wpdb->query($wpdb->prepare($SQL, $Title));
            
        if (isset($Result['error']) && $Result['error']) {  
            $this->NotificationError($Result['error']);            
        } else {
            $this->NotificationConfirmation(esc_html__($this->TDPrefix.'List.ConfirmItem'.($Id===0?'Created':'Updated'), $this->TextDomain));
        }
              
        // update current id after insertion
        if ($Id === 0) {
            $Id= intval($wpdb->insert_id);           
        }
             
        // render form
        $this->Id= $Id;
        $this->DisplayForm();
    }
    
    
    
        
  
    
}

?>