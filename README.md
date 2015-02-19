zp_openstreetmap
==============

A [Zenphoto](http://www.zenphoto.org) plugin for displaying an OpenStreetMap with the embeded geo coordinates of an image or the images of an album as markers.

Alternatively you can also create custom maps.

Scripts used
- http://leafletjs.com 
- https://github.com/Leaflet/Leaflet.markercluster
 
License: GPL v3 or later 
  
Usage
----------
Copy the theme files (file and folder) to your `plugins` folder and enable the plugin.

###Standard theme usage
Place the function `printOpenStreetMap()` on your theme's `album.php` and/or `image.php` where you wish the map to appear. If there is no metadata available the map will not be printed.

###Custom maps
You can also create maps from any image or album using the zpOpenStreetMap class (see the file for more detailed documentation on what properties you can set):

```php
$object = newImage('<albumname>','<imagefilename>'); 
$map = new zpOpenStreetMap(NULL,$object);
$map->printMap();
```
You can do the same with an album object to get the geodata from all of its images so they are placed as markers on the map.

Or you can use the template function: 

```php
printOpenStreetMap(NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, $object);`
```

Custom maps without any image or album involved work the same way. Just passing an array to the custructor. This array must be structured like this:

```php
$geodata = array(
	  array(
	    'lat' => <latitude>,
	    'long' => <longitude>,
	    'title' => 'some title for the marker popup',
	    'desc' => 'some description for the marker popup',
	    'thumb' => 'some html for the marker popup' // e.g. <img>  
	  )
);
$map = new zpOpenStreetMap($geodata);
$map->printMap();

//You can do the same with the template function
printOpenStreetMap($geodata);
````
This would create one marker. If you need more add more arrays to the array.
