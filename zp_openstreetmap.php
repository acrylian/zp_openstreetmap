<?php
/**
 * Plugin for showing OpenStreetMap maps using LeafletJS (http://leafletjs.com) for images or images from albums with embeded geodata or maps with custom geodata.
 * Also includes the marker cluster plugin https://github.com/Leaflet/Leaflet.markercluster by Dave Leaver.
 * 
 * @author Malte Müller (acrylian)
 * @package plugins
 * @subpackage media
 */
$plugin_is_filter = 5 | THEME_PLUGIN;
$plugin_description = gettext("Plugin for showing OpenStreetMap maps using LeafletJS for images or images from albums with embeded geodata.");
$plugin_author = "Malte Müller (acrylian)";
$plugin_version = '1.0';
$option_interface = 'zpOpenStreetMapOptions';

zp_register_filter('theme_head', 'zpOpenStreetMap::scripts');

class zpOpenStreetMapOptions {

  function __construct() {
    setOptionDefault('osmap_width', '100%'); //responsive by default!
    setOptionDefault('osmap_height', '300px');
    setOptionDefault('osmap_zoom', 4);
    setOptionDefault('osmap_minzoom', 2);
    setOptionDefault('osmap_maxzoom', 18);
    setOptionDefault('osmap_controlpos', 'topleft');
    setOptionDefault('osmap_maptiles', 'OpenStreetMap_Mapnik');
    setOptionDefault('osmap_clusterradius', 40);
    setOptionDefault('osmap_markerpopup', 1);
    setOptionDefault('osmap_markerpopup_thumb', 1);
    setOptionDefault('osmap_showscale', 1);
    setOptionDefault('osmap_showalbummarkers', 0);
  }

  function getOptionsSupported() {
    return array(
        gettext('Map dimensions—width') => array(
            'key' => 'osmap_width',
            'type' => OPTION_TYPE_TEXTBOX,
            'desc' => gettext("Width of them map including the unit name e.g 100% (default for responsive map), 100x or 100em.")),
        gettext('Map dimensions—height') => array(
            'key' => 'osmap_height',
            'type' => OPTION_TYPE_TEXTBOX,
            'desc' => gettext("Height of them map including the unit name e.g 100% (default for responsive map), 100x or 100em.")),
        gettext('Map zoom') => array('key' => 'osmap_zoom', 'type' => OPTION_TYPE_TEXTBOX,
            'desc' => gettext("Default zoom level.")),
        gettext('Map minimum zoom') => array('key' => 'osmap_minzoom', 'type' => OPTION_TYPE_TEXTBOX,
            'desc' => gettext("Default minimum zoom level possible.")),
        gettext('Map maximum zoom') => array('key' => 'osmap_maxzoom', 'type' => OPTION_TYPE_TEXTBOX,
            'desc' => gettext("Default maximum zoom level possible.")),
        gettext('Controls position') => array(
            'key' => 'osmap_maptiles',
            'type' => OPTION_TYPE_SELECTOR,
            'order' => 4,
            'selections' => array(
                gettext('Top left') => 'topleft',
                gettext('Top right') => 'topright',
                gettext('Bottom left') => 'bottomleft',
                gettext('Bottom right') => 'bottomright'
            ),
            'desc' => gettext('Position of the map controls')),
        gettext('Map tiles') => array(
            'key' => 'osmap_maptiles',
            'type' => OPTION_TYPE_SELECTOR,
            'order' => 4,
            'selections' => array(
                'OpenStreetMap_Mapnik' => 'OpenStreetMap_Mapnik',
                'OpenStreetMap_BlackAndWhite' => 'OpenStreetMap_BlackAndWhite',
                'OpenStreetMap_DE' => 'OpenStreetMap_DE',
                'OpenStreetMap_HOT' => 'OpenStreetMap_HOT',
                'Thunderforest_OpenCycleMap' => 'Thunderforest_OpenCycleMap',
                'Thunderforest_Transport' => 'Thunderforest_Transport',
                'Thunderforest_Landscape' => 'Thunderforest_Landscape',
                'Thunderforest_Outdoors' => 'Thunderforest_Outdoors',
                'OpenMapSurfer_Roads' => 'OpenMapSurfer_Roads',
                'MapQuestOpen_OSM' => 'MapQuestOpen_OSM',
                'MapQuestOpen_Aerial' => 'MapQuestOpen_Aerial',
                'Stamen_Toner' => 'Stamen_Toner',
                'Stamen_TonerBackground' => 'Stamen_TonerBackground',
                'Stamen_TonerLite' => 'Stamen_TonerLite',
                'Stamen_Watercolor' => 'Stamen_Watercolor'
            ),
            'desc' => gettext('The map tile provider to use. Only free providers are included. If you wish to use any commercial provider you have to use the class base of this plugin programmatically.')),
        gettext('Cluster radius') => array(
            'key' => 'osmap_clusterradius',
            'type' => OPTION_TYPE_TEXTBOX,
            'desc' => gettext("The radious when marker clusters should be used.")),
        gettext('Marker popups') => array(
            'key' => 'osmap_markerpopup',
            'type' => OPTION_TYPE_CHECKBOX,
            'desc' => gettext("Enable this if you wish info popups on the map markers. Only for album context or custom geodata.")),
        gettext('Marker popups with thumbs') => array(
            'key' => 'osmap_markerpopup_thumb',
            'type' => OPTION_TYPE_CHECKBOX,
            'desc' => gettext("Enable if you want to show thumb of images in the marker popups. Only for album context.")),
        gettext('Show scale') => array(
            'key' => 'osmap_showscale',
            'type' => OPTION_TYPE_CHECKBOX,
            'desc' => gettext("Enable if you want to show scale overlay (kilometers and miles).")),
        gettext('Show cursor position') => array(
            'key' => 'osmap_showcursorpos',
            'type' => OPTION_TYPE_CHECKBOX,
            'desc' => gettext("Enable if you want to show the coordinates if moving the cursor over the map.")),
        gettext('Show album markers') => array(
            'key' => 'osmap_showalbummarkers',
            'type' => OPTION_TYPE_CHECKBOX,
            'desc' => gettext("Enable if you want to show the map on the single image page not only the marker of the current image but all markers from the album. The current position will be highlighted."))
    );
  }

}

/**
 * The base class
 */
class zpOpenStreetMap {

  /**
   * Contains the array of the image or images from albums geodata
   * @var array
   */
  var $geodata = NULL;

  /**
   * Contains a string presenting a Javascript array of geodata for leafletjs
   * @var array
   */
  var $geodatajs = NULL;

  /**
   * geodata array('min' => array(lat,lng), 'max => array(lat,lng))
   * Default created from an image or the images of an album. 
   * @var array
   */
  var $fitbounds = NULL;

  /**
   * geodata array(lat,lng)
   * Default created from an image or the images of an album. 
   * @var array
   */
  var $center = NULL;

  /**
   * Optional lass name to attach to the map html
   * @var string
   */
  var $class = '';

  /**
   * "single" (one marker)
   * "cluster" (several markers always clustered)
   * "single-cluster" (markers of the images of the current album with the current image highlighted)
   * Default created by the $geodata property: "single "if array with one entry, "cluster" if more entries
   * @var string
   */
  var $mode = NULL;
  
   /**
   * 
   * Default false if set to true on single image maps the markers of all other images are shown as well.
   * The current image's position will be highlighted.
   * @var bool
   */
  var $showalbummarkers = false;
  /**
   * geodata array(lat,lng)
   * Default created from the image marker or from the markers of the images of an album if in context
   * @var array
   */
  var $mapcenter = NULL;

  /**
   * Unique number if using more than one map on a page
   * @var int
   */
  var $mapnumber = '';

  /**
   * Default 100% for responsive map. Values like "100%", "100px" or "100em"
   * Default taken from plugin options
   * @var string
   */
  var $width = '100%';

  /**
   * Values like "100px" or "100em"
   * Default taken from plugin options
   * @var string 
   */
  var $height = NULL;

  /**
   * Default zoom state
   * Default taken from plugin options
   * @var int
   */
  var $zoom = NULL;
  var $minzoom = NULL;
  var $maxzoom = NULL;

  /**
   * The tile provider to use. Select from the $tileprovider property like $this->maptiles = $this->tileprover['<desired provier']
   * Default taken from plugin options
   * Must be like array('<map provider url>','<attribution as requested>')
   * Default taken from plugin options
   * @var array
   */
  var $maptiles = NULL;

  /**
   * Radius when clusters should be created on more than one marker
   * Default taken from plugin options
   * @var int
   */
  var $clusterradius = NULL;

  /**
   * If used on albums or several custom markers if you wish popups on the markers
   * If using custom markers you need to provide the content for the popups withn the $geodata property
   * Default taken from plugin options
   * @var bool
   */
  var $markerpopup = false;

  /**
   * Only if on an album page and if $imagepopups are enabled.
   * If the imagepopus should contain thumbs of the images
   * Default taken from plugin options
   * @var bool
   */
  var $markerpopup_thumb = false;
  var $showmarkers = true;

  /**
   * Position of the map controls: "topleft", "topright", "bottomleft", "bottomright"
   * Default taken from plugin options
   * @var string
   */
  var $controlpos = NULL;
  
  var $showscale = NULL;
  var $showcursorpos = NULL;
  
  /**
   * The current image or album object if not passing custom geodata
   * @var object
   */
  var $obj = NULL; 
  /**
   * Predefined array of all free map tile providers for Open Street Map
   * @var type 
   */
  var $tileproviders = array(
      'OpenStreetMap_Mapnik' => 'OpenStreetMap_Mapnik',
      'OpenStreetMap_BlackAndWhite' => 'OpenStreetMap_BlackAndWhite',
      'OpenStreetMap_DE' => 'OpenStreetMap_DE',
      'OpenStreetMap_HOT' => 'OpenStreetMap_HOT',
      'Thunderforest_OpenCycleMap' => 'Thunderforest_OpenCycleMap',
      'Thunderforest_Transport' => 'Thunderforest_Transport',
      'Thunderforest_Landscape' => 'Thunderforest_Landscape',
      'Thunderforest_Outdoors' => 'Thunderforest_Outdoors',
      'OpenMapSurfer_Roads' => 'OpenMapSurfer_Roads',
      'MapQuestOpen_OSM' => 'MapQuestOpen_OSM',
      'MapQuestOpen_Aerial' => 'MapQuestOpen_Aerial',
      'Stamen_Toner' => 'Stamen_Toner',
      'Stamen_TonerBackground' => 'Stamen_TonerBackground',
      'Stamen_TonerLite' => 'Stamen_TonerLite',
      'Stamen_Watercolor' => 'Stamen_Watercolor'
  );

  /**
   * If no $geodata array is passed the function gets geodata from the current image or the images of the current album
   * if in appropiate context.
   * 
   * Alternatively you can pass an image or album object directly. This ignores the $geodata parameter then.
   * 
   * The $geodata array requires this structure:
   * Single marker:
   * 
   * array(
   *   array(
   *      'lat' => <latitude>,
   *      'long' => <longitude>,
   *      'title' => 'some title',
   *      'desc' => 'some description',
   *      'thumb' => 'some html' // an <img src=""> call or else. 
   *   )
   * );
   * 
   * If you use html for title, desc or thumb be sure to use double quotes for attributes to avoid JS conflicts.
   * For several markers add more arrays to the array. 
   *
   * If you neither pass $geodata, an object or there is no current image/album you can still display a map.
   * But in this case you need to set the $center and $fitbounds properties manually before printing a map.
   *
   * @global string $_zp_gallery_page
   * @param array $geodata Array as noted above if no current image or album should be used 
   * @param obj Image or album object If set this object is used and $geodatat is ignored if set as well
   */
  function __construct($geodata = NULL, $obj = NULL) {
    global $_zp_gallery_page, $_zp_current_image, $_zp_current_album, $_zp_current_search;
    $this->center = $this->getCenter();
    $this->fitbounds = $this->getFitBounds();
    $this->width = getOption('osmap_width');
    $this->height = getOption('osmap_height');
    $this->zoom = getOption('osmap_zoom');
    $this->minzoom = getOption('osmap_minzoom');
    $this->maxzoom = getOption('osmap_maxzoom');
    $this->maptiles = $this->tileproviders[getOption('osmap_maptiles')];
    $this->clusterradius = getOption('osmap_clusterradius');
    $this->markerpopup = getOption('osmap_markerpopup');
    $this->controlpos = getOption('osmap_controlpos');
    $this->showscale = getOption('osmap_showscale');
    $this->showcursorpos = getOption('osmap_showcursorpos');
    $this->markerpopup_thumb = getOption('osmap_markerpopup_thumb');
    $this->showalbummarkers = getOption('osmap_showalbummarkers');
    if (is_object($obj)) {
      if (isImageClass($obj)) {
        $this->obj = $obj;
        $this->mode = 'single';
      } else if (isAlbumClass($obj)) {
        $this->obj = $obj;
        $this->mode = 'cluster';
      }
    } else {
      if (is_array($geodata)) {
        if (count($geodata) < 1) {
          $this->mode = 'single';
        } else {
          $this->mode = 'cluster';
        }
        $this->geodata = $geodata;
      } else {
        switch ($_zp_gallery_page) {
          case 'image.php':
            if($this->showalbummarkers) {
              $this->obj = $_zp_current_album;
              $this->mode = 'single-cluster';
            } else {
              $this->obj = $_zp_current_image;
              $this->mode = 'single';
            }
            break;
          case 'album.php':
          case 'favorites.php':
            $this->obj = $_zp_current_album;
            $this->mode = 'cluster';
          case 'search.php':
            $this->obj = $_zp_current_search;
            $this->mode = 'cluster';
            break;
        }
      }
    }
    $this->geodata = $this->getGeoData();
  }

  /**
   * Assigns the needed JS and CSS
   */
  static function scripts() {
    ?>
    <link rel="stylesheet" type="text/css" href="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/leaflet.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/MarkerCluster.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/MarkerCluster.Default.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/zp_openstreetmap.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/L.Control.MousePosition.css" />
    <script src="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/leaflet.js"></script>
    <script src="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/leaflet.markercluster.js"></script>
    <script src="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/tile-definitions.js"></script>
    <script src="<?php echo FULLWEBPATH . '/' . USER_PLUGIN_FOLDER; ?>/zp_openstreetmap/L.Control.MousePosition.js"></script>
    <?php
  }

  /**
   * $returns coordinate informations for an image
   * Adapted from the offical Zenphoto GoogleMap plugin by Stephen Billard (sbillard) & Vincent Bourganel (vincent3569)
   * @param $image	image object
   */
  static function getImageGeodata($image) {
    global $_zp_current_image;
    $result = array();
    if (isImageClass($image)) {
      $exif = $image->getMetaData();
      if ((!empty($exif['EXIFGPSLatitude'])) && (!empty($exif['EXIFGPSLongitude']))) {
        $lat_c = explode('.', str_replace(',', '.', $exif['EXIFGPSLatitude']) . '.0');
        $lat_f = round((float) abs($lat_c[0]) + ($lat_c[1] / pow(10, strlen($lat_c[1]))), 12);
        if (strtoupper(@$exif['EXIFGPSLatitudeRef']{0}) == 'S') {
          $lat_f = -$lat_f;
        }
        $long_c = explode('.', str_replace(',', '.', $exif['EXIFGPSLongitude']) . '.0');
        $long_f = round((float) abs($long_c[0]) + ($long_c[1] / pow(10, strlen($long_c[1]))), 12);
        if (strtoupper(@$exif['EXIFGPSLongitudeRef']{0}) == 'W') {
          $long_f = -$long_f;
        }
        $thumb = "<a href='" . $image->getLink() . "'><img src='" . $image->getThumb() . "' alt='' /></a>";
        $current = 0;
        if($this->mode = 'single-cluster' && isset($_zp_current_image) && $image->filename == $_zp_current_image->filename && $image->getAlbumname() == $_zp_current_image->getAlbumname()) {
          $current = 1;
        }
        $result = array(
            'lat' => $lat_f,
            'long' => $long_f,
            'title' => shortenContent(js_encode($image->getTitle()),50,'...').'<br />',
            'desc' => shortenContent(js_encode($image->getDesc()),100,'...'),
            'thumb' => $thumb,
            'current' => $current
        );
      }
    }
    return $result;
  }

  /**
   * Gathers the map data for an album
   * Adapted from the offical Zenphoto GoogleMap plugin by Stephen Billard (sbillard) & Vincent Bourganel (vincent3569)
   * @param $album		album object
   */
  static function getAlbumGeodata($album) {
    $result = array();
    $images = $album->getImages(0, 0, null, null, false);
    foreach ($images as $an_image) {
      $image = newImage($album, $an_image);
      $imggeodata = self::getImageGeodata($image);
      if(!empty($imggeodata)) {
        $result[] = $imggeodata;
      }
    }
    return $result;
  }

  /**
   * Extracts the geodata from an image or the images of an album 
   * and creates the JS arrays for leaflet including title, description and thumb if set.
   * @return array
   */
  function getGeoData() {
    global $_zp_current_image, $_zp_current_album;
    $geodata = array();
    if (!is_null($this->geodata)) {
      return $this->geodata;
    }
    switch ($this->mode) {
      case 'single':
        $imggeodata = self::getImageGeodata($this->obj);
        if(!empty($imggeodata)) {
          $geodata = array($imggeodata);
        }
        break;
      case 'cluster':
      case 'single-cluster':
        $albgeodata = self::getAlbumGeodata($this->obj);
        if(!empty($albgeodata)) {
          $geodata = $albgeodata;
        }
        break;
    }
    if (empty($geodata)) {
      return NULL;
    } else {
      return $this->geodata = $geodata;
    }
  }
  
  /**
   * Processes the geodata returned by getGeoData() and formats it to a string 
   * presenting a multidimensional Javascript array for use with leafletjs
   * @return string
   */
  function getGeoDataJS() {
    if (!is_null($this->geodatajs)) {
      return $this->geodatajs;
    }
    $geodata = $this->getGeoData();
    if(!empty($geodata)) {
      $count = -1;
      $js_geodata = '';
      foreach ($geodata as $geo) {
        $count++;
        $js_geodata .= ' geodata[' . $count . '] = {
                  lat : "' . $geo['lat'] . '",
                  long : "' . $geo['long'] . '",
                  title : "' . shortenContent($geo['title'],50,'...') . '",
                  desc : "' . shortenContent($geo['desc'],100,'...') . '",
                  thumb : "' . $geo['thumb'] . '",
                  current : "' . $geo['current'] . '"
                };';
      }
      return $this->geodatajs = $js_geodata;
    }
  }
  
  /**
   * Returns the bounds the map should fit based on the geodata of an image or images of an album
   * @return array
   */
  function getFitBounds() {
    if (!is_null($this->fitbounds)) {
      return $this->fitbounds;
    }
    $geodata = $this->getGeoData();
    if (!empty($geodata)) {
      $geocount = count($geodata);
      $bounds = '';
      $count = '';
      foreach ($geodata as $g) {
        $count++;
        $bounds .= '[' . $g['lat'] . ',' . $g['long'] . ']';
        if ($count < $geocount) {
          $bounds .= ',';
        }
      }
      $this->fitbounds = $bounds;
    }
    return $this->fitbounds;
  }

  /**
   * Returns the center point of the map. On an single image it is the marker of the image itself.
   * On images from an album it is calculated from their geodata
   * @return array
   */
  function getCenter() {
    if (!is_null($this->center)) {
      return $this->center;
    }
    $geodata = $this->getGeoData();
    if(!empty($geodata)) {
      switch ($this->mode) {
        case 'single':
          $this->center = array($geodata[0]['lat'], $geodata[0]['long']);
          break;
        case 'cluster':
          //for demo tests only needs to be calculated properly later on!
          $this->center = array($geodata[0]['lat'], $geodata[0]['long']);  
          break;
        case 'single-cluster':
          //for demo tests only needs to be calculated properly later on!
          $this->center = array($geodata[0]['lat'], $geodata[0]['long']);  
          break;
      }
    }
    return $this->center;
  }

  /**
   * Prints the required HTML and JS for the map
   */
  function printMap() {
    global $_zp_current_image;
    $class = '';
    if (!empty($this->class)) {
      $class = ' class="' . $this->class . '"';
    }
    $geodataJS = $this->getGeoDataJS();
		?>
		<div id="osm_map<?php echo $this->mapnumber; ?>"<?php echo $class; ?> style="width:<?php echo $this->width; ?>; height:<?php echo $this->height; ?>;"></div>
		<script>
    var geodata = new Array();
    <?php echo $geodataJS; ?>
    var map = L.map('osm_map<?php echo $this->mapnumber; ?>', {
      center: [<?php echo $this->center[0]; ?>,<?php echo $this->center[1]; ?>], 
      zoom: <?php echo $this->zoom; ?>, //option
      zoomControl: false, // disable so we can position it below
      minZoom: <?php echo $this->minzoom; ?>,
      maxZoom: <?php echo $this->maxzoom; ?>,
      layers: [<?php echo $this->maptiles; ?>] //option => prints variable name stored in tile-definitions.js
    });
    var currentIcon = L.icon({
      iconUrl: 'images/marker-icon.png',
      iconRetinaUrl: 'images/marker-icon-2x.png'
    });
    var otherIcon = L.icon({
      iconUrl: 'images/marker-icon-grey.png.png',
      iconRetinaUrl: 'images/marker-icon-grey-2x.png'
    });
			<?php 
			if($this->mode == 'cluster' && $this->fitbounds) { 
				?>
				map.fitBounds([<?php echo $this->fitbounds; ?>]);
			<?php } ?>
			<?php if($this->showscale) { ?>
				L.control.scale().addTo(map);
			<?php } ?>
			
			L.control.zoom({position: '<?php echo $this->controlpos; ?>'}).addTo(map);
			<?php if($this->showcursorpos) { ?>
				L.control.mousePosition().addTo(map);
			<?php } ?>
			
		<?php
		if($this->showmarkers && !empty($geodataJS)) {
			switch ($this->mode) {
				case 'single':
					?>
						var marker = L.marker([geodata[0]['lat'], geodata[0]['long']]).addTo(map); // from image
					<?php
					break;
				case 'cluster':
					?>
						var markers_cluster = new L.MarkerClusterGroup({ maxClusterRadius: <?php echo $this->clusterradius; ?> }); //radius > Option
						$.each(geodata, function (index, value) {
							var icon = currentIcon;
       var text = '';
      <?php if ($this->markerpopup) { ?>
								text = value.title;
						<?php if ($this->markerpopup_thumb) { ?>
									text += value.thumb;
						<?php } ?>
								text += value.desc;
					<?php } ?>
       <?php if (isset($_zp_current_image) && $this->mode == 'single-cluster') { ?>
         if(value.current === 1) {
           icon = currentIcon;
         } else {
           icon = otherIcon;
         }
       <?php } ?>
       if (text === '') {
								markers_cluster.addLayer(L.marker( [value.lat, value.long], { icon: icon } ));
							} else {
								markers_cluster.addLayer(L.marker( [value.lat, value.long], { icon: icon } ).bindPopup(text));
							}
						});
						map.addLayer(markers_cluster);
					<?php
					break;
			}
		}
		?>
		</script>
		<?php
	}

}

// osm class end

/**
 * Template function wrapper for the zpOpenStreetMap class to show a map with geodata markers 
 * for the current image or collected the images of an album.
 * 
 * The map is not shown if there is no geodata available.
 * 
 * @global obj $_zp_current_album
 * @global obj $_zp_current_image
 * @global string $_zp_gallery_page
 * @param array $geodata Array of the geodata to create and display markers. See the constructor of the zpOpenStreetMap Class for the require structure
 * @param string $width Width with unit, e.g. 100%, 100px, 100em
 * @param string $height Height with unit, e.g. 100px, 100em
 * @param array $mapcenter geodata array(lat,lng);
 * @param int $zoom Number of the zoom 0 - 
 * @param array $fitbounds geodata array('min' => array(lat,lng), 'max => array(lat,lng))
 * @param string $class Class name to attach to the map element
 * @param int $mapnumber If calling more than one map per page an unique number is required
 * @param obj $obj Image or album object to skip current image or album and also $geodata
 */
function printOpenStreetMap($geodata = NULL, $width = NULL, $height= NULL, $mapcenter = NULL, $zoom = NULL, $fitbounds = NULL, $class = '', $mapnumber = NULL,$obj = NULL) {
  if (!empty($class)) {
    $class = ' class="' . $class . '"';
  }
  $map = new zpOpenStreetMap($geodata,$obj);
  if (!is_null($width)) {
    $map->width = $width;
  }
  if (!is_null($height)) {
    $map->height = $height;
  }
  if (!is_null($mapcenter)) {
    $map->center = $mapcenter;
  }
  if (!is_null($zoom)) {
    $map->zoom = $zoom;
  }
  if (!is_null($fitbounds)) {
    $map->fitbounds = $fitbounds;
  }
  if (!is_null($class)) {
    $map->class = $class;
  }
  if (!is_null($mapnumber)) {
    $map->mapnumber = $mapnumber;
  }
  $map->printMap();
}