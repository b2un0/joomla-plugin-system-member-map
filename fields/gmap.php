<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2014 - 2015 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class JFormFieldGMap extends JFormField
{
    protected $type = 'GMap';

    protected function getInput()
    {
		$plugin = JPluginHelper::getPlugin('system', 'membermap');
        $params = new JRegistry(!empty($plugin) ? $plugin->params : null);

        $doc = JFactory::getDocument();
        $doc->addScript('//maps.google.com/maps/api/js?sensor=false');

        $type = strtoupper($params->get('type', 'ROADMAP'));
        $zoom = $params->get('zoom', 1);
        $lat = $params->get('lat', 42);
        $lng = $params->get('lng', 11);

        $onload = <<<EOL
			window.membermap = {};
		    window.membermap.initialize = function() {
			 	window.membermap.options = {
			 	    center : new google.maps.LatLng({$lat}, {$lng}),
			 	    zoom : {$zoom},
			 	    mapTypeId: google.maps.MapTypeId.{$type},
      				navigationControl: false,
      				mapTypeControl: false
  				}

		        window.membermap.map = new google.maps.Map(document.getElementById('gmap'), window.membermap.options);

		        //google.maps.event.addListener(window.membermap.map, 'dragend', window.membermap.updateLatLong);
				google.maps.event.addListener(window.membermap.map, 'zoom_changed', window.membermap.updateZoom);
				google.maps.event.addListener(window.membermap.map, 'drag', window.membermap.updateLatLong);
		    }

			window.membermap.updateZoom = function(){
				$('jform_params_zoom').value = window.membermap.map.getZoom();
			}

			window.membermap.updateLatLong = function(){
				$('jform_params_lat').value = window.membermap.map.getCenter().lat();
				$('jform_params_lng').value = window.membermap.map.getCenter().lng();
			}
            
		    google.maps.event.addDomListener(window,'load', window.membermap.initialize);
EOL;

        $doc->addScriptDeclaration($onload);

        return '<div id="gmap" style="width:400px;height:300px;"></div>';
    }
}