/**
 * @author      Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link        http://www.z-index.net
 * @copyright   (c) 2014 Branko Wilhelm
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

window.membermap = {};
window.membermap.config = {};
window.membermap.config.bounce = true; // TODO
window.membermap.config.lat = 51; // TODO
window.membermap.config.lng = 10; // TODO
window.membermap.config.zoom = 6; // TODO
window.membermap.config.type = 'HYBRID'; // TODO https://developers.google.com/maps/documentation/javascript/maptypes?hl=de
window.membermap.config.center = true; // TODO

window.membermap.fn = {};

window.membermap.fn.initialize = function () {
    if (typeof window.membermap.users != 'object') {
        return;
    }

    window.membermap.google = {};
    window.membermap.google.options = {
        zoom: window.membermap.config.zoom,
        mapTypeId: google.maps.MapTypeId[window.membermap.config.type],
        center: new google.maps.LatLng(window.membermap.config.lat, window.membermap.config.lng)
    };
    window.membermap.google.map = new google.maps.Map(document.getElementById('membermap'), window.membermap.google.options);
    window.membermap.google.geocoder = new google.maps.Geocoder();
    window.membermap.google.bounds = new google.maps.LatLngBounds();
    window.membermap.google.centered = false;

    google.maps.event.addListenerOnce(window.membermap.google.map, 'idle', window.membermap.fn.geocode);

    window.setInterval(window.membermap.fn.center, 750);
}

window.membermap.fn.marker = function (user) {
    window.membermap.users[user].marker = new google.maps.Marker({
        title: window.membermap.users[user].name,
        position: window.membermap.users[user].position,
        animation: google.maps.Animation.DROP,
        map: window.membermap.google.map,
        icon: new google.maps.MarkerImage(window.membermap.users[user].avatar, null, null, null, new google.maps.Size(30, 30))
    });

    google.maps.event.addListener(window.membermap.users[user].marker, 'click', function () {
        window.location.href = window.membermap.users[user].url;
    });

    google.maps.event.addListener(window.membermap.users[user].marker, 'mouseover', function () {
        this.setZIndex(1000);
        if (window.membermap.config.bounce) {
            this.setAnimation(google.maps.Animation.BOUNCE);
        }
    });

    google.maps.event.addListener(window.membermap.users[user].marker, 'mouseout', function () {
        if (window.membermap.config.bounce) {
            this.setAnimation(null);
        }
    });

    window.membermap.google.bounds.extend(window.membermap.users[user].position);
}

window.membermap.fn.center = function () {
    if (window.membermap.config.center == true) {
        for (user in window.membermap.users) {
            if (!window.membermap.users[user].position) {
                window.membermap.config.center = false;
            }
        }

        if (window.membermap.config.center == true && window.membermap.centered == false) {
            window.membermap.google.map.fitBounds(window.membermap.bounds); // TODO center map only if configured
            window.membermap.google.centered = true;
        }
    }
}

window.membermap.fn.geocode = function () {
    for (user in window.membermap.users) {
        if (!window.membermap.users[user].position) {
            window.membermap.google.geocoder.geocode({'address': window.membermap.users[user].address}, function (user) {
                return(function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        window.membermap.users[user].position = results[0].geometry.location;
                        window.membermap.fn.marker(user);
                    } else {
                        window.setTimeout(window.membermap.fn.geocode, 750);
                    }
                });
            }(user));
        }
    }
}

google.maps.event.addDomListener(window, 'load', window.membermap.fn.initialize);