<?php

/**
 * Administratin of tags.
 * 
 * @package mFloorMap
 */
class mFloorMap_Admin_TagManager extends mFloorMap_Admin_BaseManager {

    
    protected $MenuSlug= 'mfloormap_ta';

    protected $Table= 'mfloormap_tags';
    
    protected $TDPrefix= 'ta.';
    
    protected $BulkActions= array(
        'Delete' => '__Delete',
    );   

    protected $Columns=  array(
        'Headers'=> array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'Id' => 'ID',
            'Title' => '__Title'/* def */,
            'Facility'=> '__Column.Facility|mFloorMap',
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
            'Title'    => $R ? $R['Title']             : '',
            'FacId'    => $R ? intval($R['FacId'])     : 0,
        );
    }
    
    
    /**
     * Render form (for existing and new items).
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
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_name">'.esc_html__('Column.Facility','mFloorMap').'</label>
                </th>
                <td>
                    '.$this->RenderSelect('facility', $this->GetFacilitiesList(), $this->ItemData['FacId']).'                    
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
            'T1.Facility'=> 'T2.Title',
        );
        
        $OrderBy= parent::GetSearchOrder();        
        return str_replace(array_keys($Replace), array_values($Replace), $OrderBy);
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
    //                              Actions
    // -----------------------------------------------------------------------
    
    
    /**
     * Action: modify data.
     */
    protected function Action_Update() {
        
        global $wpdb;
        
        // get para
        $Id    = $this->Input('id', 'I');
        $Title = $this->Input('title', 'T');
        $FacId = $this->Input('facility', 'I');
             
        // storing
        $SQL= $Id === 0
            ? "INSERT INTO $this->Table (Id, Title, FacId) VALUES(0, '%s', %d)"
            : "UPDATE $this->Table SET Title='%s', FacId=%d WHERE $this->PrimaryKey=".intval($Id);
        $Result= $wpdb->query($wpdb->prepare($SQL, $Title, $FacId));
            
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