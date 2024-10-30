<?php
?>

<div id="FloorMapSearchPanel">
    <div><?php echo esc_html__('Map.Search.Name', 'mFloorMap');?> <span class="SearchByName"></span></div>
    <div><?php echo esc_html__('Map.Search.Tag', 'mFloorMap');?> <span class="SearchByTag"></span></div>
</div>

<div id="FloorMap">
 
   <img src="<?php echo $FloorImageSrc; ?>" class="map" usemap="#FloorMapMap">
 
   <map name="FloorMapMap"><?php echo $Areas; ?>
   </map>

    <script type="text/javascript">
        jQuery.noConflict();
        (function($){  $().ready(function(){
            FloorMap.Init("<?php echo $UploadsURL; ?>", "<?php echo $PluginURL; ?>", <?php echo json_encode($Colors);?>);
            FloorMap.SetData({<?php echo implode(', ',$DataArray); ?>},<?php echo $FacTags; ?>);});
        })(jQuery);
    </script>
        
</div>

