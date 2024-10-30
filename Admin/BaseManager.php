<?php

/**
 *  This class is base for all other managers.
 *  It is wrapper of wp-list-table class.
 */

if (!class_exists('WP_List_Table')) {
    require_once trailingslashit(ABSPATH).'wp-admin/includes/class-wp-list-table.php';
}


class mFloorMap_Admin_BaseManager extends WP_List_Table {


    protected $MenuSlug= 'mfloormap_fa';        // "wordpress menu slug" of this manager

    protected $Table= 'mfloormap_facilities';   // name of database table

    protected $PrimaryKey= 'Id';                // primary key of database table

    protected $ShortCode= 'mfloormap-facility'; // code for shortcode generation in table column

    protected $TextDomain= 'mFloorMap';         // internationalization text domain

    protected $TDPrefix= '';                    // text domain prefix, used for composite translations

    protected $ConstructorOptions= array(
        'singular' => 'item', //singular name of the listed records
        'plural' => 'items', //plural name of the listed records
        'ajax' => false	  //does this table support ajax?
    );

    protected $BulkActions= array(
        //'Edit' => '__Edit',
        'Delete' => '__Delete',
        //'change_cat' => 'Change Category',
        //'set_off' => 'Set Offline',
        //'set_on' => 'Set Online',
    );

    protected $Columns=  array(
        'Headers'=> array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'name' => '__Name'/* def */,
            'size' => '__Size'/* def */,
            'desc' => '__Description'/* def */,
            'date' => '__Date'/* def */,
        ),
        'Sortable'=> array(
            'name' => array('file_display_name', false),
            'size' => array('file_size', false),
            'desc' => array('file_description', false),
            'date' => array('file_date', false),
        ),
        'Hidden'=> array(
        ),
    );

    // internal properites
    protected $Id= 0;                       // value of 'id' field of currently focused record
    protected $ItemData;                    // loaded record
    protected $UploadsDir= '';              // path to directory of images
    protected $UploadsURL= '';              // URL to directory of images
    protected $Action;                      // current action
    protected $SearchJoins= '';             // additional sql joins for filtering list


    /**
     * Constructor.
     */
    public function __construct() {

        global $wpdb;
        $this->Table= $wpdb->prefix.$this->Table;
        $UD= wp_get_upload_dir();           // reassign UploadsDir because "uploads" can be reallocated
        $this->UploadsDir= $UD['basedir'].'/mfloormap';
        $this->UploadsURL= $UD['baseurl'].'/mfloormap';

        // parent
        parent::__construct($this->ConstructorOptions);

        // translate definitions
        $this->BulkActions= $this->TranslateDefinitions($this->BulkActions);
        $this->Columns['Headers']= $this->TranslateDefinitions($this->Columns['Headers']);
    }


    /**
     * Execute specific action on this manager.
     */
    public function Run() {

        $Act= $this->Input('action', 'T');
        $this->Action= $Act;

        $Method= method_exists($this, 'Action_'.$Act)
            ? 'Action_'.$Act
            : 'Action_List';
        $this->$Method();
    }


    /**
     * Helper utility for fetching values from request (POST/GET).
     * Sanitizer is string consist of following letters:
     *  - "A": result must be array, other modifiers will be applied for each element of array
     *  - "I": result must be integer
     *  - "T": function "trim" will be applied on result string
     *
     * @param string $Key
     * @param string $Sanitize
     * @param mixed $Default
     * @return mixed
     */
    protected function Input($Key, $Sanitize='I', $Default=null) {

        if (isset($_POST[$Key])) {
            return $this->InputSanitizer($_POST[$Key], $Sanitize);
        }
        if (isset($_GET[$Key])) {
            return $this->InputSanitizer($_GET[$Key], $Sanitize);
        }
        return $Default !== null
            ? $Default
            : (strpos($Sanitize, 'A') !== false ? array()
                : (strpos($Sanitize, 'I') !== false ? 0 : ''));
    }

    /**
     * Helper method for Input().
     * @param mixed $Value
     * @param string $Sanitize
     * @return mixed
     */
    protected function InputSanitizer($Value, $Sanitize) {

        if (strpos($Sanitize, 'A') !== false) {
            // if value is array call this method recursive for each element
            if (!is_array($Value)) {
                return array();        // serious logical error
            }
            array_walk($Value, array($this,'InputModifier'), str_replace('A','',$Sanitize));
            return $Value;
        }
        if (strpos($Sanitize, 'I') !== false) {  // convert to integer
            $Value= intval($Value);
        }
        if (strpos($Sanitize, 'T') !== false) {  // trim string
            $Value= trim($Value);
        }
        if (is_string($Value)) {  // remove wp's "magic" quotes
            $Value= stripslashes($Value);
        }
        return $Value;
    }


    /**
     * List of available bulk actions in table.
     * Overriding parent.
     */
    protected function get_bulk_actions() {

        return $this->BulkActions;
    }


    /**
     * Getters of columns definitions.
     * Overriding parent.
     */
    public function get_columns() {

        return $this->Columns['Headers'];
    }

    protected function get_sortable_columns() {

        return $this->Columns['Sortable'];
    }

    protected function get_hidden_columns() {

        return $this->Columns['Hidden'];
    }


    /**
     * Gets the name of the default primary column.
     * Overriding parent.
     */
    protected function get_default_primary_column_name() {

        return $this->PrimaryKey;
    }


    /**
     * Executes bulk actions.
     */
    protected function HandleBulkActions() {

        $Items= $this->Input('items', 'AI');
        if (!$this->current_action() || !$this->Input('action2') || empty($Items)) {
            return;
        }
        $Count= 0;
        switch ($this->current_action()) {
            case 'Delete':
                foreach ($Items as $Id) {
                    if (!$this->CanDelete($Id)) {
                        continue;
                    }
                    $Count++;
                    $this->DeleteItem($Id);
                }
                if ($Count > 0) {
                    $Message= sprintf(esc_html__('ConfirmNItemsDeleted', $this->TextDomain), $Count);
                    $this->NotificationConfirmation($Message);
                }
                break;
        }

    }


    /**
     * Check wether item can be deleted.
     *
     * @param mixed $Id
     * @return boolean
     */
    protected function CanDelete($Id) {

        return true;
    }


    /**
     * Perform deleting specified item.
     * Called by bulk operatations handler.
     */
    protected function DeleteItem($Id) {

        // delete record from database
        global $wpdb;
        $Result= $wpdb->delete($this->Table, array($this->PrimaryKey => $Id), array('%d'));

        // notify about errors
        if (isset($Result['error']) && $Result['error']) {
            $this->NotificationError($Result['error'].'<br /><a href="javascript:history.back()">'. __("Back").'</a>');
        }
    }


    /**
     * Handle reordering items in tables woth "Ordering" column.
     *
     * @param integer $Id  database id of target item
     * @param string $Direction  either 'Up' or 'Dn'
     */
    protected function ReorderItem($Id, $Direction) {

        global $wpdb;

        // get list of orders
        $Where= $this->GetSearchWhere();
        $Order= $this->GetSearchOrder();
        $SQL= "SELECT T1.$this->PrimaryKey, T1.Ordering FROM $this->Table AS T1 $this->SearchJoins $Where ORDER BY $Order";
        $Items= $wpdb->get_results($SQL, ARRAY_A);

        // find target item
        $TargetKey= -1;
        foreach($Items as $Key=>$Item) {
            if ($Item[$this->PrimaryKey] === (string)$Id) {
                $TargetKey= $Key;
                break;
            }
        }

        $TargetKey2= $Direction === 'Up'
            ? $TargetKey-1
            : $TargetKey+1;

        if ($TargetKey === -1 || !isset($Items[$TargetKey2])) {
            return;
        }

        // swap ordering values
        $SQL= "UPDATE $this->Table SET Ordering=%d WHERE $this->PrimaryKey=%d";

        $Result= $wpdb->query($wpdb->prepare($SQL, $Items[$TargetKey2]['Ordering'], $Id));
        if (isset($Result['error']) && $Result['error']) {
            $this->NotificationError($Result['error']);
            return;
        }

        $Result= $wpdb->query($wpdb->prepare($SQL, $Items[$TargetKey]['Ordering'], $Items[$TargetKey2][$this->PrimaryKey]));
        if (isset($Result['error']) && $Result['error']) {
            $this->NotificationError($Result['error']);
            return;
        }
    }


    /**
     * Handle image uploading.
     */
    protected function UploadImage($UploadKey, $DatabaseKey, $SubDir, $ItemId, $AddSuffix='') {

        $Exts=  array('gif','png','jpg','jpe','jpeg');
        if (!isset($_FILES[$UploadKey])) {
            return;
        }
        // is there upload attempt
        if ($_FILES[$UploadKey]['tmp_name'] === 'none' || $_FILES[$UploadKey]['tmp_name'] === ''
              || $_FILES[$UploadKey]['size'] === 0 || !is_uploaded_file($_FILES[$UploadKey]['tmp_name'])) {
            return;
        }

        // get exiting image
        global $wpdb;
        $Results= $wpdb->get_results("SELECT $DatabaseKey FROM $this->Table WHERE $this->PrimaryKey=$ItemId LIMIT 1", ARRAY_A);
        $ExistingImage= $Results[0][$DatabaseKey];

        // validate uploading image
        $NameParts= explode('.', basename($_FILES[$UploadKey]['name']));
        $Ext= strtolower(array_pop($NameParts));
        if (!in_array($Ext, $Exts)) {
            $this->NotificationError(esc_html(sprintf(__('Error.ImageUpload.Ext','mFloorMap'),implode(',',$Exts))));
            return;
        }
        $BaseName= sanitize_file_name(implode('.', $NameParts));
        $BaseName= preg_replace("/[^(\x20-\x7F)]*/", "", $BaseName ); // remove non-ascii letters
        if (strlen($BaseName) === 0) {
            $this->NotificationError(esc_html(sprintf(__('Error.ImageUpload.Name','mFloorMap'),$BaseName)));
            return;
        }

        // ensure existance of file directory
        if (!is_dir($this->UploadsDir.'/'.$SubDir)) {
            mkdir($this->UploadsDir.'/'.$SubDir, 0775, true);
            chmod($this->UploadsDir.'/'.$SubDir, 0775);
        }

        // delete existing file
        if ($ExistingImage <> '' && is_file("$this->UploadsDir/$SubDir/$ExistingImage")) {
            unlink("$this->UploadsDir/$SubDir/$ExistingImage");
        }

        // new filename contains ID to easily identify for which database entity this image is used,
        // and timestamp to mitigate browser's caching after uploading newer image
        $NewName= $ItemId.'-'.$BaseName.$AddSuffix.'-'.time().'.'.$Ext;

        // move uploaded image to target directory
        if (!move_uploaded_file($_FILES[$UploadKey]['tmp_name'], "$this->UploadsDir/$SubDir/$NewName")) {
            $this->NotificationError(esc_html__('Error.ImageUpload.Write','mFloorMap').": $this->UploadsDir/$SubDir/$NewName");
            return;
        }

        // update database
        $SQL= "UPDATE $this->Table SET $DatabaseKey='%s' WHERE $this->PrimaryKey=".intval($ItemId);
        $Result= $wpdb->query($wpdb->prepare($SQL, $NewName));
        if (isset($Result['error']) && $Result['error']) {
            $this->NotificationError($Result['error']);
        }
    }



    //------------------------------------------------------------------------
    //                          Column renderers
    // -----------------------------------------------------------------------


    /*
     * Renderers of cell content.
     */
    protected function column_default($Item, $ColumnName) {

        //if (strpos($column_name, 'file_') !== 0) $column_name = "file_" . $column_name;
        return $Item[$ColumnName];
    }

    public function column_cb($Item) {

        return sprintf('<input type="checkbox" name="items[]" value="%s" />', $Item['Id']);
    }

    protected function column_title($Item) {

        $URL= admin_url("admin.php?page={$this->MenuSlug}&action=View&id=$Item[Id]"
            .(defined('DOING_AJAX')?"&redirect_referer=1":""));

        $Title= esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $Item['Title']));
        $Caption= ($Item['Title']) ? esc_html($Item['Title']) : '?';      // at least "?" need to be clickable

        return '<a class="row-title" href="'.$URL.'" title="'.$Title.'">'.$Caption.'</a>';
    }

    protected function column_published($Item) {

        return $Class= $Item['Published'] === '1'
            ? '<span class="CheckMarkYes">&#x2713;</span>'
            : '<span class="CheckMarkNo">&#x2717;</span>';     // &#x2613; &#x2716;
    }

    protected function column_shortcode($Item) {

        return '['.$this->ShortCode.' id="'.$Item[$this->PrimaryKey].'"]';
    }

    protected function column_ordering($Item) {

        $Column   = $this->Input('orderby', 'T');
        $Direction= $this->Input('order', 'T');

        return $Column === 'Ordering' && $Direction === "asc"
            ? '
            <del class="ReorderArrowUp button">&#x25B2;</del> &nbsp; &nbsp;
            <del class="ReorderArrowDn button">&#x25BC;</del>'
            : '-';
    }



    //------------------------------------------------------------------------
    //                          Actions
    // -----------------------------------------------------------------------


    /**
     * Action: create new item.
     */
    protected function Action_New() {

        $this->Id= 0;
        $this->DisplayForm();
    }


    /**
     * Action: show details of selected item and allow editing it.
     */
    protected function Action_View() {

        $Id= $this->Input('id', 'I');
        if ($Id) {
            $this->Id= $Id;
            $this->DisplayForm();
        } else {
            echo 'Invalid id.';
        }
    }


    /**
     * Default action: display list of items.
     */
    protected function Action_List() {

        // execute bulk operations
        $this->HandleBulkActions();

        $BtnAddURL= admin_url("admin.php?page=$this->MenuSlug&action=New");
        $BtnAddText= __('Btn.ListAdd', $this->TextDomain);
        $BtnAdd= '<a href="'.$BtnAddURL.'" class="add-new-h2">'.$BtnAddText.'</a>';
        $H2Text= __($this->TDPrefix.'List.Caption', $this->TextDomain);
        $Nonce='';//wp_nonce_field(__FILE__, $this->MenuSlug.'-nonce', false, false);
?>
<div class="wrap mFloorMap">
    <h2><?=$H2Text?> <?=$BtnAdd?></h2>
    <?=$this->FormatNotifications()?>
    <form id="ItemsList" method="get" action="">
	<?=$Nonce?>
        <input type="hidden" name="page" value="<?=$this->MenuSlug?>">
        <?php
        $this->prepare_items();
        $this->display();
        ?>
    </form>
</div>
<?php
    }


    /**
     * Action: move item in list one position up or down.
     */
    protected function Action_Reorder() {

        // get para
        $Items    = $this->Input('items', 'AI');
        $Direction= $this->Input('action2', 'T');  // note that 'Up' & 'Dn' are injected into action2 by javasript
        $Order    = $this->Input('order', 'T');

        // validate
        if (count($Items) === 1 && in_array($Direction, array('Up','Dn'))) {

            // if table is ordered in opposite direction then invert $Direction
            if ($Order === "desc") {
                $Direction= $Direction === 'Up' ? 'Dn' : 'Up';
            }

            // execute reorder
            $this->ReorderItem($Items[0], $Direction);
        }

        // show table
        $this->Action_List();
    }



    //------------------------------------------------------------------------
    //                 CRUD table preparation utilities
    // -----------------------------------------------------------------------


    /*
     * Overriding parent.
     */
    function prepare_items() {

        global $wpdb;

        // table headers
        $this->_column_headers= array(
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
            $this->PrimaryKey
        );

        // pagination
        $Page= $this->get_pagenum();
        $PerPage= 50;

        $Where= $this->GetSearchWhere();
        $Order= $this->GetSearchOrder();

        $TotalCount= $this->SearchCount($Where);
        $this->items= $this->Search($Where, $Order, $PerPage, $Page);

        if (empty($this->items) && !empty($wpdb->last_error)) {
            wp_die("<b>Database error</b>: " . $wpdb->last_error);
        }

        $this->set_pagination_args(array(
            'total_items' => $TotalCount,
            'per_page' => $PerPage,
            'total_pages' => ceil($TotalCount/$PerPage),
        ));
    }



    protected function GetSearchWhere() {

        $Conditions= array();
        $View= $this->Input('view', 'T');
        switch ($View) {
            case 'Published': $Conditions[]= 'T1.Published=1'; break;
            case 'Hidden': $Conditions[]= 'T1.Published=0'; break;
        }
        return empty($Conditions)
            ? ''
            : 'WHERE '.implode(' AND ', $Conditions);
    }


    protected function GetSearchOrder() {

        $Direction= $this->Input('order', 'T') === "desc"
            ? "DESC"
            : "ASC";
        $OrderBy= $this->Input('orderby', 'T');

        // validate column
        foreach($this->Columns['Sortable'] as $Def) {
            if ($Def[0] === $OrderBy) {
                return "T1.$OrderBy $Direction";   // yes, we found it
            }
        }
        return "T1.$this->PrimaryKey DESC";
    }




    protected function Search($Where, $Order, $PerPage, $Page) {

        global $wpdb;
        $Offset= ($Page-1)*$PerPage;
        $SQL= "SELECT * FROM $this->Table AS T1 $this->SearchJoins $Where ORDER BY $Order LIMIT $Offset,$PerPage";
        $Items= $wpdb->get_results($SQL, ARRAY_A);
        return $Items;
    }


    protected function SearchCount($Where) {

        global $wpdb;
        $Count= $wpdb->get_var("SELECT COUNT(T1.$this->PrimaryKey) FROM $this->Table AS T1 $this->SearchJoins $Where");
        return intval($Count);
    }



    //------------------------------------------------------------------------
    //                        Translation helpers
    // -----------------------------------------------------------------------


    protected function TranslateDefinition($String) {

        if (substr($String, 0, 2) <> '__') {
            return $String;
        }
        $Parts= explode('|', $String, 2);
        $Text= substr($Parts[0], 2);

        return isset($Parts[1])
            ? __($Text, $Parts[1] === '@' ? $this->TextDomain : $Parts[1])
            : __($Text);
    }


    protected function TranslateDefinitions($Array) {

        return array_map(array($this, 'TranslateDefinition'), $Array);
    }



    //------------------------------------------------------------------------
    //                        Notification methods
    // -----------------------------------------------------------------------

    protected $Notifications= array(
        'Error'=> array(),
        'Confirmation'=> array(),
    );

    protected function NotificationError($Message) {

        $this->Notifications['Error'][]= $Message;
    }

    protected function NotificationConfirmation($Message) {

        $this->Notifications['Confirmation'][]= $Message;
    }

    protected function FormatNotifications() {

        $Blocks= array();
        foreach ($this->Notifications['Error'] as $Message) {
            $Blocks[]= '<div id="message" class="error fade"><i class="dashicons dashicons-warning"></i><p>'.esc_html($Message).'</p></div>';
        }
        foreach ($this->Notifications['Confirmation'] as $Message) {
            $Blocks[]= '<div id="message" class="updated fade"><i class="dashicons dashicons-yes"></i><p>'.esc_html($Message).'</p></div>';
        }
        return implode("\n\n", $Blocks);
    }



    //------------------------------------------------------------------------
    //                           HTML helpers
    // -----------------------------------------------------------------------

    protected function RenderSelect($CtrlName, $List, $CurrValue, $Id='') {

        $Options= '';
        $Id= $Id ? ' id="'.$Id.'"' : '';
        foreach($List as $k=>$v) {
            $Selected= $k === $CurrValue ? ' selected="selected"' : '';
            $Options .= '<option value="'.esc_attr($k).'"'.$Selected.'>'.esc_html($v).'</option>';
        }
        return '<select name="'.$CtrlName.'" size="1"'.$Id.'>'.$Options.'</select>';
    }


    protected function GetFacilitiesList() {

        global $wpdb;
        $Facilities= array(0=>' -');
        $SQL= 'SELECT * FROM '.$wpdb->prefix.'mfloormap_facilities';
        $Results= $wpdb->get_results($SQL, ARRAY_A);
        foreach($Results as $F) {
            $Facilities[intval($F['Id'])]= $F['Title'];
        }
        return $Facilities;
    }


    protected function GetFloorsList($FirstItem) {

        global $wpdb;
        $List= array();
        if ($FirstItem) {
            $List[0]= $FirstItem;
        }
        $SQL= 'SELECT T1.Id, T1.Title, T2.Title AS FacTitle'
            .' FROM '.$wpdb->prefix.'mfloormap_floors AS T1'
            .' LEFT JOIN '.$wpdb->prefix.'mfloormap_facilities AS T2 ON T1.FacId=T2.Id'
            .' ORDER BY T1.Ordering';
        $Results= $wpdb->get_results($SQL, ARRAY_A);
        foreach($Results as $F) {
            $List[intval($F['Id'])]= "$F[Title] ($F[FacTitle])";
        }
        return $List;
    }


    protected function GetTagsList($FirstItem) {

        global $wpdb;
        $List= array();
        if ($FirstItem) {
            $List[0]= $FirstItem;
        }
        $SQL= 'SELECT T1.Id, T1.Title, T2.Title AS FacTitle'
            .' FROM '.$wpdb->prefix.'mfloormap_tags AS T1'
            .' LEFT JOIN '.$wpdb->prefix.'mfloormap_facilities AS T2 ON T1.FacId=T2.Id'
            .' ORDER BY T1.Ordering';
        $Results= $wpdb->get_results($SQL, ARRAY_A);
        foreach($Results as $F) {
            $List[intval($F['Id'])]= "$F[Title] ($F[FacTitle])";
        }
        return $List;
    }


    protected function DisplayForm() {

        $this->LoadItemData();

        $FormURL= remove_query_arg(array('id', 'action'));
        $BackURL= admin_url("admin.php?page=$this->MenuSlug");
        $Heading= $this->Id === 0
            ? __($this->TDPrefix.'New.Caption', $this->TextDomain)
            : sprintf(__($this->TDPrefix.'Update.Caption', $this->TextDomain), $this->ItemData['Title']);
        echo '
<div class="wrap mFloorMap">

    <h2><span><a href="'.$BackURL.'">&#8592; '.esc_html__('Hdr.BackToList', $this->TextDomain).'</a></span>
        '.esc_html($Heading).'
    </h2>
    '.$this->FormatNotifications().'
    <form id="ItemForm" method="post" action="'.$FormURL.'" enctype="multipart/form-data" class="validate">
	<input type="hidden" name="action" value="Update" />
	<input type="hidden" name="id" value="'.$this->Id.'" />

<input type="hidden" name="orderby" value="Ordering"><input type="hidden" name="order" value="asc">


	'.wp_nonce_field(__CLASS__."-$this->Id", 'mfloormap-nonce', true, false).'
	<table class="form-table mFormTable">
            '.$this->RenderFormRows().'
	</table>
	<p class="submit">
            <input type="submit" class="button-primary" name="submit-btn" value="'.__('Btn.'.($this->Id === 0 ? 'Create' : 'Update'), $this->TextDomain).'" />
                &nbsp;
            <input type="button" class="button-secondary" name="cancel-btn" value="'.__('Back').'" onclick="document.location.href=\''.$BackURL.'\';" />
        </p>
    </form>
</div>';
    }


    /**
     * Populate $this->ItemData with values from database for existing entries (for $this->Id > 0)
     * or with empty (or default) values for new entry.
     */
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
            'Published'=> $R ? intval($R['Published']) : 0,
            'Image'    => $R ? $R['Image']             : '',
        );
    }


    /**
     * Render form controls (for existing and new items).
     * Descendant classes should override it.
     *
     * @return string
     */
    protected function RenderFormRows() {

        return '';
    }

}

?>