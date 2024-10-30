<?php

/**
 * Administation of places (objects).
 * 
 * @package mFloorMap
 */
class mFloorMap_Admin_PlaceManager extends mFloorMap_Admin_BaseManager {
    
    
    protected $MenuSlug= 'mfloormap';  // ommited "_pl"

    protected $Table= 'mfloormap_places';
    
    protected $TDPrefix= 'pl.';
    
    protected $BulkActions= array(
        'Delete' => '__Delete',
    );   

    protected $Columns=  array(
        'Headers'=> array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'Id' => 'ID',
            'Title' => '__Title'/* def */,
            'Floor'=> '__Column.Floor|mFloorMap',            
            'Published'=> '__Column.Published|mFloorMap',            
            'LocationMark'=> '__Column.LocationMark|mFloorMap',
        ),
        'Sortable'=> array(
            'Id'=> array('Id', false),
            'Title' => array('Title', false),
            'Floor'=> array('Floor', false),
            'Ordering'=> array('Ordering', false),
            'LocationMark'=> array('LocationMark', false),
        ),
        'Hidden'=> array(            
        ),
    );
    

    protected function LoadItemData() {
        
        global $wpdb;        
            
        // load from database        
        if ($this->Id > 0) {
            $SQL= 'SELECT * FROM '.$this->Table.' WHERE '.$this->PrimaryKey.'='.$this->Id.' LIMIT 1';
            $Results= $wpdb->get_results($SQL, ARRAY_A);
            $R= empty($Results) ? null : $Results[0];
        } else {
            $R= null;
        }
        
        // prepare item record
        $this->ItemData= array(
            //  key          value from database       value for new record    
            'Title'         => $R ? $R['Title']             : '',
            'OfficialTitle' => $R ? $R['OfficialTitle']     : '',
            'Published'     => $R ? intval($R['Published']) : 0,
            'Floor'         => $R ? intval($R['FloId'])     : 0,
            'LocationMark'  => $R ? $R['LocationMark']      : '',
            'Logo'          => $R ? $R['Logo']              : '',
            'Photo'         => $R ? $R['Photo']             : '',
            'Description'   => $R ? $R['Descr']             : '',
            'ContactInfo'   => $R ? $R['ContactInfo']       : '',
            'TimingInfo'    => $R ? $R['TimingInfo']        : '',                
            'Mapping'       => $R ? $R['Mapping']           : '',
            'Tags'          => array(),
        );
                
        // collect assigned tags
        if ($R) {
            $SQL= "SELECT TagId FROM {$wpdb->prefix}mfloormap_placetags WHERE PlaceId=$this->Id";
            $Tags= $wpdb->get_results($SQL, ARRAY_A);        
            foreach($Tags as $Tag) { 
                $this->ItemData['Tags'][]= $Tag['TagId'];
            }
        }
    }
    
    
    /**
     * Render form (for existing and new items).
     */
    protected function RenderFormRows() {        
        
        $Logo= $this->UploadsDir.'/logos/'.$this->ItemData['Logo'];
        $LogoURL= is_file($Logo) ? $this->UploadsURL.'/logos/'.$this->ItemData['Logo'] : '';
        $Photo= $this->UploadsDir.'/photos/'.$this->ItemData['Photo'];
        $PhotoURL= is_file($Photo) ? $this->UploadsURL.'/photos/'.$this->ItemData['Photo'] : '';
         
        // find background image
        global $wpdb;
        $SQL= 'SELECT Image FROM '.$wpdb->prefix.'mfloormap_floors WHERE '.$this->PrimaryKey.'='.intval($this->ItemData['Floor']).' LIMIT 1';
        $FloorData= $wpdb->get_results($SQL, ARRAY_A);
        $BgImage= empty($FloorData) ? '' : $FloorData[0]['Image'];        
        $BgImagePath= $this->UploadsDir.'/floors/'.$BgImage;
        $BgImageURL= is_file($BgImagePath) ? "$this->UploadsURL/floors/$BgImage" : '';
        
        // prepare map
        $Points= array_map('intval', explode(',', $this->ItemData['Mapping']));         
        $MinimumTop= $MinimumLeft= 99999;
        $SVG_points= array();
        $BgImgWH= is_file($BgImagePath) 
            ? getimagesize($BgImagePath) 
            : array(0,0);         
        $x=0;
        while(isset($Points[$x+1])) {
            $SVG_points[]= $Points[$x].','.$Points[$x+1];
            $MinimumLeft= min($Points[$x], $MinimumLeft);
            $MinimumTop= min($Points[$x+1], $MinimumTop);
            $x++; $x++;
        }
        $SVG_points= implode(' ',$SVG_points);        
        if ($MinimumLeft>99000) {
          // seems that no points has defined jet, place viewport on center of map
          $MinimumLeft= intval($BgImgWH[0]/2)-160;
          $MinimumTop= intval($BgImgWH[1]/2)-80;
        }
        // bounce 100px away from border
        $MinimumLeft= max(0, $MinimumLeft-100);
        $MinimumTop= max(0, $MinimumTop-100);
        
        // insert javascript files into header
        wp_enqueue_script(__FILE__, plugin_dir_url(__FILE__).'JS/mFloorMapPlaces.js', array('jquery'), mFloorMap::GetInstance()->GetVersion(), false);
        wp_enqueue_script("jquery-ui-draggable");
        
        // HTML
        return '		
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_title">'.esc_html__('Name').'</label>
                </th>
                <td>
                    <input type="text" name="title" id="item_title" value="'.esc_attr(trim($this->ItemData['Title'])).'" size="40" maxlength="250" autocomplete="off" />
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_officialtitle">'.esc_html__('Edit.OfficialTitle','mFloorMap').'</label>
                </th>
                <td>
                    <input type="text" name="officialtitle" id="item_officialtitle" value="'.esc_attr(trim($this->ItemData['OfficialTitle'])).'" size="40" maxlength="250" autocomplete="off" />
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
                    <label for="item_floor">'.esc_html__('Column.Floor','mFloorMap').'</label>
                </th>
                <td>
                    '.$this->RenderSelect('floor', $this->GetFloorsList(' -'), $this->ItemData['Floor'], 'item_floor').'                    
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_locmark">'.esc_html__('Column.LocationMark','mFloorMap').'</label>
                </th>
                <td>
                    <input type="text" name="locationmark" id="item_locmark" value="'.esc_attr(trim($this->ItemData['LocationMark'])).'" size="12" maxlength="12" autocomplete="off" />
                </td>
            </tr>   
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_logo">'.esc_html__('Edit.Logo','mFloorMap').'</label>
                </th>
                <td>
                    <a href="'.$LogoURL.'" target="_blank">
                        <img src="'.$LogoURL.'" height="50" class="LogoThumb">
                    </a>
                    <span style="margin-left:4em">
                        '.esc_html__('Edit.ImageReplace','mFloorMap').' &nbsp; 
                        <input type="file" name="logo" id="item_logo" />
                    </span>    
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_photo">'.esc_html__('Edit.Photo','mFloorMap').'</label>
                </th>
                <td>
                    <a href="'.$PhotoURL.'" target="_blank">
                        <img src="'.$PhotoURL.'" height="100" class="PhotoThumb">
                    </a>
                    <span style="margin-left:4em">
                        '.esc_html__('Edit.ImageReplace','mFloorMap').' &nbsp; 
                        <input type="file" name="photo" id="item_photo" />
                    </div>    
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_desc">'.esc_html__('Edit.Description','mFloorMap').'</label>
                </th>
                <td>
                    <textarea name="description" id="item_desc" rows="4">'.esc_html($this->ItemData['Description']).'</textarea>
                </td>
            </tr> 
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_contact">'.esc_html__('Edit.ContactInfo','mFloorMap').'</label>
                </th>
                <td>
                    <input type="text" name="contactinfo" id="item_contact" value="'.esc_attr($this->ItemData['ContactInfo']).'" size="40" maxlength="250" autocomplete="off" />
                </td>
            </tr> 
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_timing">'.esc_html__('Edit.TimingInfo','mFloorMap').'</label>
                </th>
                <td>
                    <input type="text" name="timinginfo" id="item_timing" value="'.esc_attr($this->ItemData['TimingInfo']).'" size="40" maxlength="250" autocomplete="off" />
                </td>
            </tr>             
            '.($this->Id === 0 ? '' :'
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_tags">'.esc_html__('Edit.Tags','mFloorMap').'</label>
                </th>
                <td class="TagArray">
                    '.implode("\n                    ", $this->RenderTagControls()).'
                </td>
            </tr> 
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="item_mapping">'.esc_html__('Edit.Mapping','mFloorMap').'</label>
                    <div style="float:right">    
                        <button type="button" class="BtnZoom" z="1">x1</button>
                        <button type="button" class="BtnZoom" z="2">x2</button>
                        <button type="button" class="BtnZoom" z="4">x4</button>
                    </div>
                </th>
                <td>
                    <div id="mFloorMapMappingContainer" style="float:left">
                        <svg id="SVG_layer" width="500" height="500">
                            <polygon id="SVG_poly" points="'.$SVG_points.'" stroke="#34a" stroke-width="2" fill="#66a" fill-opacity="0.6" stroke-dasharray="3,2" />
                        </svg>
                        <img src="'.$BgImageURL.'" alt="" width="1" />
                    </div> 
                    
                    <hr style="clear:both">
                    <div style="position:relative; padding-right:5em">
                        <input type="text" name="mapping" id="item_mapping" value="'.esc_attr($this->ItemData['Mapping']).'" size="40" maxlength="250" autocomplete="off" />
                        <button type="button" onclick="FloorMapAdm.PopulatePolygon();" style="position:absolute;right:0;top:0;">'.esc_html__('Edit.UpdatePoly','mFloorMap').'</button>    
                    </div>        

                    <script language="javascript" type="text/javascript">
                      (function($){$().ready(function(){FloorMapAdm.Init('.$BgImgWH[0].','.$BgImgWH[1].', '.$MinimumLeft.','.$MinimumTop.');});})(jQuery);
                    </script>  
                </td>
            </tr> ').'
';
    }

    
    protected function RenderTagControls() {
        
        // find FacId
        global $wpdb;
        $SQL= "SELECT T2.FacId"
            ." FROM $this->Table AS T1"
            ." LEFT JOIN {$wpdb->prefix}mfloormap_floors AS T2 ON T2.Id=T1.FloId"            
            ." WHERE T1.Id=$this->Id";
        $Items= $wpdb->get_results($SQL, ARRAY_A);
        $FacId= isset($Items[0]) ? intval($Items[0]['FacId']) : 0;
        if (!$FacId) {
            return array();  // item has not assigned to any facility, therefore it has no tags
        }
        
        // find all tags
        global $wpdb;
        $SQL= "SELECT Id, Title FROM {$wpdb->prefix}mfloormap_tags WHERE FacId=$FacId ORDER BY Ordering";
        $AllTags= $wpdb->get_results($SQL, ARRAY_A);   
        
        // render
        $Controls= array();
        foreach($AllTags as $Tag) {           
            $Chk= in_array($Tag['Id'], $this->ItemData['Tags'], true) ? ' checked="checked"': '';
            $CtrlId= 'item_tag'.$Tag['Id'];
            $Control= ' <input type="checkbox" name="tags[]" value="'.$Tag['Id'].'" id="'.$CtrlId.'"'.$Chk.' />';
            $Controls[]= '<label for="'.$CtrlId.'">'.$Control.' '.esc_html($Tag['Title']).'</label>';
        }
        return $Controls;
    }
    
    
    
    
    protected function Search($Where, $Order, $PerPage, $Page) {
        
        global $wpdb;
        $Offset= ($Page-1)*$PerPage;
        $SQL= "SELECT T1.*, T2.Title AS FloorTitle, T3.Title AS FacTitle"
            ." FROM $this->Table AS T1"
            ." LEFT JOIN {$wpdb->prefix}mfloormap_floors AS T2 ON T2.Id=T1.FloId"
            ." LEFT JOIN {$wpdb->prefix}mfloormap_facilities AS T3 ON T3.Id=T2.FacId"
            ." ".$this->SearchJoins
            ." $Where ORDER BY $Order LIMIT $Offset,$PerPage";
        $Items= $wpdb->get_results($SQL, ARRAY_A);
        return $Items;
    }
    
    
        
    protected function GetSearchOrder() {
     
        $Replace= array(
            'T1.Floor'=> 'T2.Title',
        );
        
        $OrderBy= parent::GetSearchOrder();        
        return str_replace(array_keys($Replace), array_values($Replace), $OrderBy);
    }

    
    protected function GetSearchWhere() {
        
        global $wpdb;
        $Conditions= array();
        // filter by floor
        $FilterFloor= $this->Input('filter_floor', 'I');
        if ($FilterFloor > 0) {
            $Conditions[]= 'T1.FloId='.$FilterFloor; 
        }        
        // filter by tag
        $FilterTag= $this->Input('filter_tag', 'I');
        if ($FilterTag > 0) {
            $Conditions[]= 'TT.TagId='.$FilterTag; 
            $this->SearchJoins .= ' LEFT JOIN '.$wpdb->prefix.'mfloormap_placetags AS TT ON TT.PlaceId=T1.Id';
        }        
        // pack all conditions
        return empty($Conditions)
            ? ''
            : 'WHERE '.implode(' AND ', $Conditions);
    }
    

    /**
     * Insert filtering controls next to the bulk actions.
     */
    protected function extra_tablenav($which) {
    
        if ($which <> 'top') {return;}        
        echo '
            <div class="alignleft actions">
            </div>
            <div class="alignleft actions">';
        // filter by floor
	echo '<label class="screen-reader-text" for="filter_floor">'.esc_html__('Column.Floor','mFloorMap').'</label>';
        $List= $this->GetFloorsList(__('pl.Filter.AllFloors','mFloorMap'));
        $Curr= $this->Input('filter_floor', 'I');
        $Control= $this->RenderSelect('filter_floor', $List, $Curr, 'filter_floor');
        echo $Control;
        // filter by tag
        echo '<label class="screen-reader-text" for="filter_tag">'.esc_html__('Edit.Tags','mFloorMap').'</label>';
        $List= $this->GetTagsList(__('pl.Filter.AllTags','mFloorMap'));
        $Curr= $this->Input('filter_tag', 'I');
        $Control= $this->RenderSelect('filter_tag', $List, $Curr, 'filter_tag');
        echo $Control;
        // button
        submit_button( __('Filter'), '', 'filter_action', false, array('id'=>'post-query-submit'));        
        echo '</div>';        
    }
        
    
    /**
     * Perform deleting specified item.
     * Called by bulk operatations handler.
     */
    protected function DeleteItem($Id) {
        
        // find and delete existing images
        global $wpdb;
        $Results= $wpdb->get_results("SELECT * FROM $this->Table WHERE $this->PrimaryKey=$Id LIMIT 1", ARRAY_A);
        $Logo= isset($Results[0]) ? $Results[0]['Logo'] : '';
        if ($Logo) {
            unlink("$this->UploadsDir/logos/$Logo");
        }
        $Photo= isset($Results[0]) ? $Results[0]['Photo'] : '';
        if ($Photo) {
            unlink("$this->UploadsDir/photos/$Photo");
        }
        
        // delete tag assignments        
        $wpdb->delete($wpdb->prefix.'mfloormap_placetags', array('PlaceId'=>$Id));  
        
        // call parent
        parent::DeleteItem($Id);
    }
    
    

    //------------------------------------------------------------------------
    //                          Column renderers
    // -----------------------------------------------------------------------
    
    
    /**
     * Renderer of "facility" cell content.
     */
    function column_floor($Item) {

        $Title= $Item['FloorTitle']; 
        if ($Item['FacTitle']) {            
            $Title .= ' ('.$Item['FacTitle'].')';        
        }
        return ($Title)
            ? $Title
            : ' -';
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
        $Id           = $this->Input('id', 'I');
        $Title        = $this->Input('title', 'T');
        $OfficialTitle= $this->Input('officialtitle', 'T');
        $Published    = $this->Input('published', 'I');
        $Floor        = $this->Input('floor', 'I');
        $LocMark      = $this->Input('locationmark', 'T');
        $Description  = $this->Input('description', 'T');
        $ContactInfo  = $this->Input('contactinfo', 'T');
        $TimingInfo   = $this->Input('timinginfo', 'T');
        $Tags         = $this->Input('tags', 'AI');
        $Mapping      = $this->Input('mapping', 'T');
                    
        // storing
        $SQL= $Id === 0
            ? "INSERT INTO $this->Table (Id, Title, OfficialTitle, Published, FloId, LocationMark, Descr, ContactInfo, TimingInfo, Mapping)"
                               ." VALUES(0, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s')"
            : "UPDATE $this->Table SET Title='%s', OfficialTitle='%s', Published=%d, FloId=%d, LocationMark='%s', Descr='%s', ContactInfo='%s', TimingInfo='%s', Mapping='%s'"
                               ." WHERE $this->PrimaryKey=".intval($Id);
        $Result= $wpdb->query($wpdb->prepare($SQL, $Title, $OfficialTitle, $Published, $Floor, $LocMark, $Description, $ContactInfo, $TimingInfo, $Mapping));
            
        if (isset($Result['error']) && $Result['error']) {  
            $this->NotificationError($Result['error']);            
        }
        
        // update current id after insertion
        if ($Id === 0) {
            $Id= intval($wpdb->insert_id);
        }
                
        // handle uploads
        if (empty($this->Notifications['Error'])) {
            $this->UploadImage('logo', 'Logo', 'logos', $Id);
            $this->UploadImage('photo', 'Photo', 'photos', $Id);
        }
        
        // handle tags
        $wpdb->delete($wpdb->prefix.'mfloormap_placetags', array('PlaceId'=>$Id));        
        if (!empty($Tags)) {
            $Multi= array();        
            foreach(array_unique($Tags) as $Tag) {
                $Multi[]= "($Id,$Tag)";
            }
            $SQL= "INSERT INTO {$wpdb->prefix}mfloormap_placetags (PlaceId, TagId) VALUES ".implode(', ',$Multi);
            $Result= $wpdb->query($SQL);     // all values are safe as integers, no need to "prepare" query
        }
        
        // disatch confirmation message
        if (empty($this->Notifications['Error'])) {
            $this->NotificationConfirmation(esc_html__($this->TDPrefix.'List.ConfirmItem'.($Id===0?'Created':'Updated'), $this->TextDomain));
        }
        
        // render form
        $this->Id= $Id;
        $this->DisplayForm();
    }
    
   
  
}

?>