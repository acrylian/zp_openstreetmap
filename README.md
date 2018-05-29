**NOTE: This plugin is now included in Zenphoto 1.5 and will be developed there from now on. This repository will be abandoned**

zp_openstreetmap
==============

A [Zenphoto](http://www.zenphoto.org) plugin for displaying an OpenStreetMap with the embeded geo coordinates of an image or the images of an album as markers.

Alternatively you can also create custom maps.

Scripts used
- http://leafletjs.com 
- https://github.com/Leaflet/Leaflet.markercluster
- https://github.com/ardhi/Leaflet.MousePosition
 
License: GPL v3 or later 
  
Usage
----------

Put the file `zp_openstreetmap.php` and the folder of the same name into your `/plugins` folder and enable it.

### Standard theme usage

Place the function `printOpenStreetMap()` on your theme's `album.php` and/or `image.php` where you wish the map to appear. If there is no metadata available the map will not be printed.

### Custom maps beyond current image/album

#### Individual images or albums

You can also create maps from any image or album using the zpOpenStreetMap class (see the file itself for more detailed documentation on which properties you can set):

```php
$albumobj = newAlbum('<albumname>');
$object = newImage($albumobj,'<imagefilename>'); 
$map = new zpOpenStreetMap(NULL,$object);
$map->printMap();
```
You can do the same with an album object to get the geodata from all of its images so they are placed as markers on the map.

Or you can use the template function: 

```php
printOpenStreetMap(NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, $object);`
```

#### Custom maps based on custom geodata

Custom maps without any image or album involved work the same way. Just passing an array to the custructor. This array must be structured like this:

```php
$geodata = array(
	  array(
	    'lat' => <latitude>,
	    'long' => <longitude>,
	    'title' => 'some title for the marker popup',
	    'desc' => 'some description for the marker popup',
	    'thumb' => 'some html for the marker popup' // e.g. <img>  
	    'current' => 0 // 1 to hightight this marker with another color. Intended for the current image on album marker view but can be used otherwise, too
	  )
);
$map = new zpOpenStreetMap($geodata);
$map->printMap();

//You can do the same with the template function although the class way is more flexible.
printOpenStreetMap($geodata);
````
This would create one marker. If you need more add more arrays to the array above.
