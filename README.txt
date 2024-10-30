=== mFloorMap ===
Contributors: tekod
Tags: map, plan, mall, chart, floormap, floorplan, guide, stores, shops, parking lot, marketplace
Requires at least: 4.5.0
Tested up to: 5.4
Stable tag: 1.0
Requires PHP: 5.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

mFloorMap is a WordPress plugin for sites that need to display floor plans for shopping centers, malls, parking lots, marketplaces...

== Description ==
This plugin is tailor-made for larger shopping centers, malls, parking lots, marketplaces, etc... to provide floor plan with details for each object on it.

It support separate management of multiple facilities and each facility can contain multiple floors.

Maps can be rendered on page/post using short-codes.
There is two types of short-codes:

* Floor - display only map of specified floor
* Facility - display array of zoomable maps which belongs to specified facility

Placing cursor over region on map will display tool-tip (cloud) with basic information about that object whether clicking on it will open separate page with full text description and attached photo.

Visitor can use "search by tag" functionality to easily find all related objects on map.
Searching controls will highlight all matched objects on all rendered maps.

= Performance & UX =
Browsing is maximally improved and accelerated by preloading of all basic object details on map(s).
As visitor moves cursor over next object details in cloud are instantly updated, only logo image will dispatch additional HTTP request if it is not cached.
Results of searching also will be instantly displayed because all needed data is already present.

= SEO =
Separated sub-pages with full text and photo will put name of selected object into "title" and "h1" tags which can be used for branding and promotion of landing pages.

= Demo =
Demonstration of plugin populated with maps of an shopping mall <a href="http://www.tekod.com/demo/wordpress/floormap-full" target="_blank">you can find here</a>.

== Installation ==

1. From WordPress dashboard go to "Plugins" > "Add New", search for "mFloorMap" and click "Install".
1. Activate plugin.
1. Installer was automatically created first facility and first floor within in. Now upload image of floormap (plan).
1. Create floor objects (places). 
1. Place short-tags somewhere on page/post to render map(s), something like: [mfloormap-facility id="1"] or [mfloormap-floor id="6"].

== Frequently Asked Questions ==

= Can I place multiple short-tags in single page? =
No, combine all maps under same facility and render that facility.

= How to remove certain unused details from cloud? =
Hook to "mFloorMap-WidgetFacility" or "mFloorMap-WidgetFloor" filters to customize rendering templates.

== Screenshots ==

1. Public page displaying facility with 3 floors, click on image will zoom its map.
2. Public page displaying single floor map.
3. Public page after click on an mapped object on floor.
4. Admin page editing floor settings.
5. Admin page editing object (place) settings.

== Changelog ==

= 1.0 =
* First version of plugin ported to WordPress platform.
