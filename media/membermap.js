/**
 * @author      Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link        http://www.z-index.net
 * @copyright   (c) 2014 Branko Wilhelm
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

window.membermap = {};
window.membermap.fn = {};
window.membermap.geocoded = 0;

window.membermap.fn.initialize = function () {
    if (typeof window.membermap.users != 'object') {
        return;
    }

    document.getElementById('membermap').style.width = window.membermap.config.width;
    document.getElementById('membermap').style.height = window.membermap.config.height + 'px';

    window.membermap.google = {};
    window.membermap.google.options = {
        zoom: window.membermap.config.zoom,
        mapTypeId: google.maps.MapTypeId[window.membermap.config.type],
        center: new google.maps.LatLng(window.membermap.config.lat, window.membermap.config.lng)
    };

    window.membermap.google.map = new google.maps.Map(document.getElementById('membermap'), window.membermap.google.options);
    window.membermap.google.geocoder = new google.maps.Geocoder();
    window.membermap.google.bounds = new google.maps.LatLngBounds();

    google.maps.event.addListenerOnce(window.membermap.google.map, 'idle', window.membermap.fn.geocode);

    if (window.membermap.config.delay) {
        window.membermap.intval = window.setInterval(window.membermap.fn.markers, window.membermap.config.delay);
    }
}

window.membermap.fn.markers = function () {
    var finished = true;
    for (user in window.membermap.users) {
        if (!window.membermap.users[user].placed && window.membermap.users[user].ready) {
            window.membermap.users[user].placed = true;
            window.membermap.fn.marker(user);
            finished = false;
            break;
        }

        if (!window.membermap.users[user].ready) {
            finished = false;
        }
    }

    if (finished) {
        window.clearInterval(window.membermap.intval);
    }
}

window.membermap.fn.marker = function (user) {
    window.membermap.users[user].marker = new google.maps.Marker({
        title: window.membermap.users[user].name,
        position: window.membermap.users[user].position,
        animation: window.membermap.config.drop ? google.maps.Animation.DROP : null,
        map: window.membermap.google.map,
        icon: new google.maps.MarkerImage(window.membermap.users[user].avatar, null, null, null, new google.maps.Size(window.membermap.config.size, window.membermap.config.size))
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

    window.membermap.geocoded++;

    window.membermap.google.bounds.extend(window.membermap.users[user].position);

    if (window.membermap.config.center && window.membermap.geocoded >= 2) {
        window.membermap.google.map.fitBounds(window.membermap.google.bounds);
    }
}

window.membermap.fn.geocode = function () {
    for (user in window.membermap.users) {
        if (!window.membermap.users[user].ready) {
            window.membermap.users[user].requests++;
            window.membermap.google.geocoder.geocode({'address': window.membermap.users[user].address}, function (user) {
                return(function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        window.membermap.users[user].position = results[0].geometry.location;
                        window.membermap.users[user].ready = true;
                        if (!window.membermap.config.delay) {
                            window.membermap.fn.marker(user);
                        }
                    } else {
                        window.setTimeout(window.membermap.fn.geocode, 500);
                        if (window.membermap.users[user].requests >= window.membermap.config.requests) {
                            window.membermap.users[user].ready = true;
                        }
                    }
                });
            }(user));
        }
    }
}

google.maps.event.addDomListener(window, 'load', window.membermap.fn.initialize);