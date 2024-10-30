<?php

/**
 * Administration of floors.
 * 
 * @package mFloorMap
 */
class mFloorMap_Admin_FloorManager extends mFloorMap_Admin_BaseManager {

    protected $MenuSlug= 'mfloormap_fl';

    protected $Table= 'mfloormap_floors';
    
    protected $ShortCode= 'mfloormap-floor';
    
    protected $TDPrefix= 'fl.';
    
    protected $BulkActions= array(
        'Delete' => '__Delete',
    );   

    
    protected $Columns=  array(
        'Headers'=> array(
            'cb' => '<input type="checkbox" />',
            'Id' => 'ID',
            'Title' => '__Title'/* def */,
            'Facility'=> '__Column.Facility|mFloorMap',
            'Published'=> '__Column.Published|mFloorMap',
            'Shortcode' => '__Shortcode'/* def */,
            'Ordering'=> '__Column.Order|mFloorMap',
        ),
        'Sortable'=> array(
            'Id'=> array('Id', false),
            'Title' => array('Title', false),
            'Facility'=> array('Facility', false),
            'Ordering'=> array('Ordering', false),
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
            'Title'    => $R ? $R['Title']              : '',
            'FacId'    => $R ? intval($R['FacId'])      : 0,
            'Published'=> $R ? intval($R['Published'])  : 0,                
            'Image'    => $R ? $R['Image']              : '',
        );
    }
    
    
    /**
     * Render form controls (for existing and new items).
     */
    protected function RenderFormRows() {
        
        $Image= $this->UploadsDir.'/floors/'.$this->ItemData['Image'];
        $ImageURL= is_file($Image) ? "$this->UploadsURL/floors/".$this->ItemData['Image'] : '';
            
        return '
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_title">'.esc_html__('Name').'</label>
                </th>
                <td>
                    <input type="text" name="title" id="item_title" value="'.esc_attr($this->ItemData['Title']).'" size="40" maxlength="250" autocomplete="off" />
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_fac">'.esc_html__('Column.Facility','mFloorMap').'</label>
                </th>
                <td>
                    '.$this->RenderSelect('facility', $this->GetFacilitiesList(), $this->ItemData['FacId'], 'item_fac').'
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_published">'.esc_html__('Column.Published','mFloorMap').'</label>
                </th>
                <td>
                    <input type="checkbox" name="published" id="item_published" value="1"'.($this->ItemData['Published']===1?' checked="checked"':'').' />
                </td>
            </tr>            
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_image">'.esc_html__('Edit.Image','mFloorMap').'</label>
                </th>
                <td>
                    <a href="'.$ImageURL.'" target="_blank">
                        <img src="'.$ImageURL.'" height="300" class="ImgThumb">
                    </a>
                    <div style="display:block; margin-top:1em">
                        '.esc_html__('Edit.ImageReplace','mFloorMap').' &nbsp; 
                        <input type="file" name="image" id="item_image" />
                    </div>    
                </td>
            </tr>';
    }
    
    
    protected function Search($Where, $Order, $PerPage, $Page) {
        
        global $wpdb;
        $Offset= ($Page-1)*$PerPage;
        $SQL= "SELECT T1.*, T2.Title AS Fac"
            ." FROM $this->Table AS T1 LEFT JOIN {$wpdb->prefix}mfloormap_facilities AS T2 ON T2.Id=T1.FacId"
            ." $Where ORDER BY $Order LIMIT $Offset,$PerPage";
        $Items= $wpdb->get_results($SQL, ARRAY_A);
        return $Items;
    }
    
    
        
    protected function GetSearchOrder() {
     
        $Replace= array(
            'T1.Facility'=> 'T1.FacId',
        );
        
        $OrderBy= parent::GetSearchOrder();        
        return str_replace(array_keys($Replace), array_values($Replace), $OrderBy);
    }



    /**
     * Perform deleting specified item.
     * Called by bulk operatations handler.
     */
    protected function DeleteItem($Id) {
        
         // find and delete existing image
        global $wpdb;
        $Results= $wpdb->get_results("SELECT * FROM $this->Table WHERE $this->PrimaryKey=$Id LIMIT 1", ARRAY_A);
        $ExistingImage= isset($Results[0]) ? $Results[0]['Image'] : '';
        if ($ExistingImage) {
            unlink("$this->UploadsDir/floors/$ExistingImage");
        }
        
        parent::DeleteItem($Id);
    }
    
    
    /**
     * Validation that selected floor can be deleted.
     */
    protected function CanDelete($Id) {
    
        // dont allow deletion if there are assigned places
        global $wpdb;
        $Count= $wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->prefix.'mfloormap_places WHERE FloId='.intval($Id));
        if ($Count === '0') {
            return true;
        }
        $Name= $wpdb->get_var('SELECT Title FROM '.$this->Table.' WHERE Id='.intval($Id));
        $this->NotificationError(sprintf(esc_html__('Error.CannotDelete.HasPlaces','mFloorMap'), $Name, $Count));
        return false;
    }
    
    
    
    //------------------------------------------------------------------------
    //                          Column renderers
    // -----------------------------------------------------------------------
    
    
    /**
     * Renderer of "facility" cell content.
     */
    function column_facility($Item) {

        return $Item['Fac'];        
    }
       
       
    
    //------------------------------------------------------------------------
    //                               Actions
    // -----------------------------------------------------------------------
    
    
    
    /**
     * Action: modify data.
     */
    protected function Action_Update() {
        
        global $wpdb;
        
        // get para
        $Id       = $this->Input('id', 'I');
        $Title    = $this->Input('title', 'T');
        $FacId    = $this->Input('facility', 'I');
        $Published= $this->Input('published', 'I');        
        
        // send to database
        $SQL= $Id === 0
            ? "INSERT INTO $this->Table (Id, Title, FacId, Published) VALUES(0, '%s', %d, %d)"
            : "UPDATE $this->Table SET Title='%s', FacId=%d, Published=%d WHERE $this->PrimaryKey=".intval($Id);
        $Result= $wpdb->query($wpdb->prepare($SQL, $Title, $FacId, $Published));
            
        // prepare notification
        if (isset($Result['error']) && $Result['error']) {  
            $this->NotificationError($Result['error']);            
        } else {
            $this->NotificationConfirmation(esc_html__($this->TDPrefix.'List.ConfirmItem'.($Id===0?'Created':'Updated'), $this->TextDomain));
        }
        
        // update current id and record's order after insertion
        if ($Id === 0) {
            $Id=  intval($wpdb->insert_id);
            $wpdb->query("UPDATE $this->Table SET Ordering=$Id WHERE $this->PrimaryKey=$Id");
        }
        
        // handle image upload
        if (empty($this->Notifications['Error'])) {
            $this->UploadImage('image', 'Image', 'floors', $Id);
        }
        
        // render form
        $this->Id= $Id;
        $this->DisplayForm();
    }
    
    
    
    /**
     * Override Action_List to append some hints.
     */
    protected function Action_List() {
        
        parent::Action_List();        
        echo '<br>'.esc_html__('Hint.EnableReordering','mFloorMap');
    }
    
    
}

?>