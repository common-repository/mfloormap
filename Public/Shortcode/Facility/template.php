<?php
?>


<div id="FloorMapSearchPanel">
    <div><?php echo esc_html__('Map.Search.Name', 'mFloorMap');?> <span class="SearchByName"></span></div>
    <div><?php echo esc_html__('Map.Search.Tag', 'mFloorMap');?> <span class="SearchByTag"></span></div>
</div>

<div id="FloorMap">
 
    <div id="FloorMapPanels">
        
<?php foreach(array_values($Floors) as $Key => $Floor) { ?>    
      <div id="FloorMap<?php echo $Key; ?>" class="FloorPanel"> 
        <img src="<?php echo $Floor['ImgSrc']; ?>" class="map" usemap="#FloorMapMap<?php echo $Floor['Id']; ?>">       
        <map name="FloorMapMap<?php echo $Floor['Id']; ?>"><?php echo $Floor['Areas']; ?>
        </map>
      </div>
<?php } ?>
    
        <div style="clear:both"></div>
    </div>    
    
    <script type="text/javascript">
        jQuery.noConflict();
        (function($){  $().ready(function(){
            FloorMap.Init("<?php echo $UploadsURL; ?>", "<?php echo $PluginURL; ?>", <?php echo json_encode($Colors);?>);
            FloorMap.SetData({<?php echo implode(', ',$DataArray); ?>},<?php echo empty($FacTags)?"[]":"['".implode("','", $FacTags)."']"; ?>);});
        })(jQuery);
    </script>
            
</div>

