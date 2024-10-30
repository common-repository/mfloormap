(function($){

    FloorMap= {

        Data: [],
        UploadDir: "",
        PluginDr: "",
        Colors: null,
        Offsets: {x:150, y:315},
        Container: null,
        //Placeholder: null,
        CanZoom: true,
        Highlighted: false,
        Mapsters: [],
        Cloud: null,
        CloudShown: false,
        CloudHover: false,
        CloudMarkup: '<div id="FloorMapCloud"><h2></h2><div class="fmLoc"></div><img src="#" /><div class="fmTel"></div><div class="fmTime"></div><div class="fmTags"></div></div>',
        
        Init: function(UploadDir,PluginDir,Colors) {            
            FloorMap.Offsets.x += $('html').position().left + $('body').position().left;
            FloorMap.Offsets.y += $('html').position().top + $('body').position().top;
            FloorMap.UploadDir= UploadDir;
            FloorMap.PluginDir= PluginDir;
            FloorMap.Colors= Colors;
            $("#FloorMap .map").each(function(key){
                FloorMap.Mapsters[key]= FloorMap.InitMapster($(this));
            });
            FloorMap.Container= $('#FloorMapPanels');
            $('#FloorMap .FloorPanel').on('click', FloorMap.OnPanelClick);
            $('#FloorMap').on('animationend', function(e){if($(e.target).hasClass('Ping')){$(e.target).remove();}});
        },
        
        InitMapster: function(Map) {
            return Map.mapster({    
                isSelectable: false,
                clickNavigate: true,
                mapKey: "data",
                fillOpacity: 0.8,
                // mouseoutDelay: 1000,
                showToolTip: false,
                stroke: true,       
                strokeWidth: 2,
                render_highlight: {
                    fade: true,             
                    fillColor: FloorMap.Colors.HighlightFill,
                    strokeColor: FloorMap.Colors.HighlightStroke,         
                },
                render_select: {
                    fade: true,              
                    fillColor: FloorMap.Colors.SelectFill,
                    strokeColor: FloorMap.Colors.SelectStroke,
                },
                onMouseover: function(data) {              
                    FloorMap.Highlighted= true;
                    FloorMap.ShowPlaceCloud(data.key, data.e.pageX, data.e.pageY);        
                },
                onMouseout: function(data) {
                    FloorMap.Highlighted= false;
                }
            });             
        },

        CreatePlaceCloud: function() {
            FloorMap.Cloud= $(FloorMap.CloudMarkup);        
            $("body").append(FloorMap.Cloud);
            $("body").mousemove(function(e){FloorMap.OnMouseMove(e);});
            FloorMap.Cloud.mouseover(function(e){FloorMap.CloudHover=true;});
            FloorMap.Cloud.mouseout(function(e){FloorMap.CloudHover=false;});
        },

        ShowPlaceCloud: function(Key, CoordX, CoordY) {
            if (!FloorMap.Cloud) FloorMap.CreatePlaceCloud();
            if (FloorMap.CloudShown === Key) return;
            FloorMap.UpdateCloudContent(FloorMap.Data[Key]);
            FloorMap.Cloud.show();      
            FloorMap.CloudShown= Key;
        },

        HidePlaceCloud: function() {
           FloorMap.CloudShown= false;
           FloorMap.Cloud.hide();
        },

        SetData: function(Data, TagList, Floors) {
            FloorMap.Data= Data;
            FloorMap.CreateSearchByName();
            FloorMap.CreateSearchByTag(TagList);
        },

        UpdateCloudContent: function(KeyContent) {
            if (!KeyContent) return;
            $("h2", FloorMap.Cloud).html(KeyContent[0]);
            $(".fmLoc", FloorMap.Cloud).html(KeyContent[1]+"<div>"+KeyContent[2]+"</div>");
            $("img", FloorMap.Cloud).attr("src", "");
            $("img", FloorMap.Cloud).attr("src", KeyContent[3] === '' 
                ? FloorMap.PluginDir+'/Image/empty-logo.png' 
                : FloorMap.UploadDir+"/logos/"+KeyContent[3]);
            $(".fmTel", FloorMap.Cloud).html(KeyContent[4]);
            $(".fmTime", FloorMap.Cloud).html(KeyContent[5]);
            var Tags= (KeyContent[6].length > 0) ? "<li>"+KeyContent[6].join("</li><li>")+"</li>" : "";
            $(".fmTags", FloorMap.Cloud).html("<ul>"+Tags+"</ul>");
        },

        OnMouseMove: function(e) {
            if (!FloorMap.Highlighted && !FloorMap.CloudHover && FloorMap.CloudShown) FloorMap.HidePlaceCloud();
            if (!FloorMap.CloudShown) return;
            if (!FloorMap.Cloud) return;
            var Left= e.pageX - FloorMap.Offsets.x;
            var Top= e.pageY - FloorMap.Offsets.y;  
            // set cloud position
            FloorMap.Cloud.css({left:Left+"px", top:Top+"px"});        
        },

        OnPanelClick: function(e) { // delegated click        
            if (!$(e.target).is('img') || !FloorMap.CanZoom) {return;}
            var Panel= $(e.target).closest('.FloorPanel');
            var PanId= Panel.attr('id').substr(8);
            var Canvases= Panel.find('canvas');
            FloorMap.CanZoom= false;
            if (Panel.hasClass('FloorPanelZoomed')) {
                // collapsing
                Panel.find('#mapster_wrap_'+PanId+', .mapster_el').css({width:'', height:''}); // allow resizing of bg image                
                Canvases.css({visibility:'hidden'});
                Panel.css(Panel[0].OldRect);
                setTimeout(function(){                  
                    Panel.removeClass('FloorPanelZoomed');
                    FloorMap.RelativizeAllPanels();
                    FloorMap.Container.css('height','');      
                    FloorMap.Mapsters[PanId].mapster('resize', Panel[0].OldRect.width, Panel[0].OldRect.height, 0);
                    Canvases.css({visibility:'visible'});
                    FloorMap.CanZoom= true;
                }, 500);
            } else {
                // expanding
                var Rect= {left:Panel.position().left, top:Panel.position().top, width:Panel.width(), height:Panel.height()};
                Panel[0].OldRect= Rect;
                var FullWidth= FloorMap.Container.width();
                var FullHeight= Math.round(FullWidth/(Rect.width/Rect.height));
                var ContainerHeight= Math.max(FullHeight, FloorMap.Container.height());
                FloorMap.Container.css('height', ContainerHeight+'px'); // set height to fixed value
                // position all panels
                FloorMap.ApsolutizeAllPanels();
                // animate
                Canvases.css({visibility:'hidden'});
                Panel.addClass('FloorPanelZoomed');  
                setTimeout(function(){
                    Panel.css({left:'0px', top:'0px', width:'100%', height:FullHeight+'px'});
                }, 50);
                //FloorMap.Container.css('height',FullHeight);            
                Panel.find('#mapster_wrap_'+PanId+', .mapster_el').css({width:'', height:''}); // allow resizing of bg image
                setTimeout(function(){
                    FloorMap.Mapsters[PanId].mapster('resize',FullWidth,FullHeight,0);
                    Canvases.css({visibility:'visible'});
                    FloorMap.CanZoom= true;
                }, 500);
            }
        },
        
        ApsolutizeAllPanels: function() {
            $('.FloorPanel').each(function(){
                var Pos= $(this).position();
                $(this).css({left:Pos.left, top:Pos.top, width:$(this).width(), height:$(this).height()});
            });
            $('.FloorPanel').each(function(){                
                $(this).addClass('FloorPanelApsolutized');
            });
        },
        
        RelativizeAllPanels: function() {
            $('.FloorPanel').each(function(){                
                $(this).removeClass('FloorPanelApsolutized').css({left:'', top:'', width:'', height:''});
            });
        },
                
        CreateSearchByName: function() {
            $("#FloorMapSearchPanel select[name=SelectByName]").remove();
            var List= [];
            var Unique= {};
            $.each(FloorMap.Data, function(Key,Value){
                if (Unique["s"+Value[0]]) return;
                Unique["s"+Value[0]]= true;
                List.push(Value[0]+"||"+Key);
            });
            List.sort();
            var Options= "<option value=\'0\'> -</option>";
            $.each(List, function(Key,Value){
                var V= Value.split("||");
                Options += "<option value=\'"+V[1]+"\'>"+V[0]+"</option>";
            });
            var Select= "<select name=\'SelectByName\'>"+Options+"</select>";
            var El= $(Select);
            $("#FloorMapSearchPanel .SearchByName").append(El);
            $("#FloorMapSearchPanel select[name=SelectByName]").change(function(){
                var Selected= $("#FloorMapSearchPanel select[name=SelectByName] option:selected").text();
                var Keys=[];
                $.each(FloorMap.Data, function(Key,Value){
                  if (Selected === Value[0]) {Keys.push(Key);}
                });
                FloorMap.Highlight(Keys); 
                $("#FloorMapSearchPanel select[name=SelectByTag]").val(-1);
            });
        },

        CreateSearchByTag: function(TagList) {
            $("#FloorMapSearchPanel select[name=SelectByTag]").remove();
            var List= "<option value=\"-1\"> -</option>";
            $.each(TagList, function(Key,Value){            
                List += '<option value="'+Key+'">'+Value+'</option>';
            });
            var Select= '<select name="SelectByTag">'+List+'</select>';
            var El= $(Select);
            $("#FloorMapSearchPanel .SearchByTag").append(El);
            $("#FloorMapSearchPanel select[name=SelectByTag]").change(function(){
              var Selected= $("#FloorMapSearchPanel select[name=SelectByTag] option:selected").text();
              var Keys=[];
              $.each(FloorMap.Data, function(Key,Value){
                  if ($.inArray(Selected, Value[6]) !== -1) {Keys.push(Key);}
              });
              FloorMap.Highlight(Keys);          
              $("#FloorMapSearchPanel select[name=SelectByName]").val(0);
            });
        },

        Highlight: function(Keys) {
            var Map= $(".map");
            var All= [];
            $.each(FloorMap.Data, function(Key,Value){All.push(Key);});
            Map.mapster("set", false, All.join(","));
            Map.mapster("set", true, Keys.join(","));
            
            $.each(Keys, function(Key,Value) {FloorMap.Ping(Value);});
        },
        
        Ping: function(K) {
            var Area= $('area[data="'+K+'"]');
            var Panel= Area.closest('div');
            var Coords= Area.attr('coords').split(',');
            var Rect= {'left':99999, 'right':-9999, 'top':9999, 'bottom':-9999};
            for(var x=Coords.length-1; x>=0; x--) {
                Rect= {
                    'left'  : Math.min(Rect.left, Coords[x-1]),
                    'right' : Math.max(Rect.right, Coords[x-1]),                        
                    'top'   : Math.min(Rect.top, Coords[x]),
                    'bottom': Math.max(Rect.bottom, Coords[x]),
                };
                x--;
            }
            console.log(Panel);
            $('<div class="Ping"></div>').css({
                left: ((Rect.left+Rect.right)/2-20)+'px',
                top : ((Rect.top+Rect.bottom)/2-20)+'px',
            }).appendTo(Panel);
        }

    };
})(jQuery);