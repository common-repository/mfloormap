
FloorMapAdm= {
  Container: null, 
  BgImg: null,
  BgImgWH: null,
  InitialOffset: null,
  SVG: null,
  Poly: null,
  InputCtrl: null,
  Zoom: 2,
          
  DraggablePolygon: function() {
    var points= FloorMapAdm.Poly[0].points;
    for (var i=0; i<points.numberOfItems; i++) {
      (function (i) { // close over variables for drag call back
        var point = points.getItem(i);
        var handle= document.createElement("div");
        handle.className= "SvgPoint";
        handle.p= i;
        FloorMapAdm.Container.append(handle);
        var base= {};  
        // center handles over polygon
        var cs= window.getComputedStyle(handle, null);
        base.left = parseInt(FloorMapAdm.SVG.css('left')) // + SVG_Parent.scrollLeft(); 
            - (parseInt(cs.width) + parseInt(cs.borderLeftWidth) + parseInt(cs.borderRightWidth))/2;
        base.top = parseInt(FloorMapAdm.SVG.css('top')) // + SVG_Parent.scrollTop();
            - (parseInt(cs.height) + parseInt(cs.borderTopWidth) + parseInt(cs.borderBottomWidth))/2; 
        handle.style.left = base.left + point.x + "px";
        handle.style.top = base.top + point.y + "px";
  
        jQuery(handle)
            .draggable({drag: function (event) {
                setTimeout(function () { // jQuery apparently calls this *before* setting position, so defer
                  point.x = parseInt(handle.style.left) - base.left;
                  point.y = parseInt(handle.style.top) - base.top;
                  FloorMapAdm.ReadPolygon();
                },50);
              }
            })
            .click(function(){
                FloorMapAdm.ReadPolygon(parseInt(this.p));
                FloorMapAdm.PopulatePolygon();
                FloorMapAdm.DraggablePolygon();
            });
      }(i));
    }
  },
  
  ReadPolygon: function(Skip) {
    var List= [];
    var Count= FloorMapAdm.Poly[0].points.numberOfItems;
    for(var x=0; x<Count; x++) {
        if (x === Skip) continue;
        var Point= FloorMapAdm.Poly[0].points.getItem(x);
        List.push(parseInt(Point.x / FloorMapAdm.Zoom)+','+parseInt(Point.y / FloorMapAdm.Zoom));
    }
    FloorMapAdm.InputCtrl.val(List.join(','));
  },
          
  PopulatePolygon: function() {
      var CSV= FloorMapAdm.InputCtrl.val();
      if (!CSV) return;
      var List= CSV.split(',');
      jQuery('#mFloorMapMappingContainer .SvgPoint').remove();      
      FloorMapAdm.Poly[0].points.clear();
      for(var x=0; x<List.length; x=x+2) {
          var Point= FloorMapAdm.SVG[0].createSVGPoint();
          Point.x= List[x] * FloorMapAdm.Zoom;
          Point.y= List[x+1] * FloorMapAdm.Zoom;
          FloorMapAdm.Poly[0].points.appendItem(Point);
      }      
  },
             
  ResizeSvgLayer: function() {    
    if (FloorMapAdm.BgImg.attr('zoomed') === FloorMapAdm.Zoom) return;
    if (!FloorMapAdm.BgImgWH[0] || !FloorMapAdm.BgImgWH[1]) {
      FloorMapAdm.SVG.css({width:'400px', height:'400px'});
      return;
    }
    var W= FloorMapAdm.BgImgWH[0] * FloorMapAdm.Zoom;
    var H= FloorMapAdm.BgImgWH[1] * FloorMapAdm.Zoom;
    FloorMapAdm.SVG.css({width:W+'px', height:H+'px'});    
    FloorMapAdm.BgImg.width(W);
    FloorMapAdm.BgImg.height(H);
    FloorMapAdm.BgImg.attr('zoomed', FloorMapAdm.Zoom);
  },
  
  SetupZoom: function(Zoom) {
    FloorMapAdm.Zoom= Zoom;
    FloorMapAdm.ResizeSvgLayer();   
    // draw polygon and make points draggable  
    FloorMapAdm.PopulatePolygon();   
    FloorMapAdm.DraggablePolygon(); 
    // focus on polygon
    FloorMapAdm.Container.scrollLeft(FloorMapAdm.InitialOffset[0] * FloorMapAdm.Zoom);
    FloorMapAdm.Container.scrollTop(FloorMapAdm.InitialOffset[1] * FloorMapAdm.Zoom); 
  },
  
  Init: function(BgImgW, BgImgH, OffsetLeft, OffsetTop) {
    FloorMapAdm.Container= jQuery('#mFloorMapMappingContainer');
    FloorMapAdm.BgImg=  jQuery('#mFloorMapMappingContainer img');
    FloorMapAdm.SVG=  jQuery('#SVG_layer');    
    FloorMapAdm.Poly=  jQuery('#SVG_layer polygon');
    FloorMapAdm.InputCtrl=  jQuery('input#item_mapping');
    FloorMapAdm.BgImgWH= [BgImgW, BgImgH];
    FloorMapAdm.InitialOffset= [OffsetLeft, OffsetTop];
    // rezize container at full width
    var w= FloorMapAdm.Container.parent().innerWidth()-60;
    var h= w*0.5;
    FloorMapAdm.Container.css({width:w+'px', height:h+'px'});
    FloorMapAdm.BgImg.attr('width','');
    // resize SVG at 100% of background image
    FloorMapAdm.ResizeSvgLayer();    
    FloorMapAdm.BgImg.bind('load', FloorMapAdm.ResizeSvgLayer); // again
    // setup initial zoom and resize playground
    FloorMapAdm.SetupZoom(2);
    // events
    jQuery('.mFloorMap .BtnZoom').on('click', function(e){
        var Z= jQuery(e.target).attr('z');
        FloorMapAdm.SetupZoom(Z);
    });
    FloorMapAdm.Container
        .mousedown(function(e){FloorMapAdm.Container.addClass('SvgTransparentPoints');})
        .mouseup(function(e){FloorMapAdm.Container.removeClass('SvgTransparentPoints');});
    var ParentOffset= FloorMapAdm.Container.offset();
    FloorMapAdm.SVG.click(function(e){ 
       FloorMapAdm.ReadPolygon();
       var Coords= FloorMapAdm.InputCtrl.val();
       if (Coords) Coords += ',';
       var NewX= (e.pageX + FloorMapAdm.Container.scrollLeft() - ParentOffset.left) / FloorMapAdm.Zoom;
       var NewY= (e.pageY + FloorMapAdm.Container.scrollTop() - ParentOffset.top) / FloorMapAdm.Zoom;
       FloorMapAdm.InputCtrl.val(Coords + parseInt(NewX)+','+parseInt(NewY));
       FloorMapAdm.PopulatePolygon();
       FloorMapAdm.DraggablePolygon();
    });    
    FloorMapAdm.Poly.mousedown(function(e){e.stopPropagation();});
  }
};
  


