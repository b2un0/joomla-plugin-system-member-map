/**
 * @author      Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link        http://www.z-index.net
 * @copyright   (c) 2014 Branko Wilhelm
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

var membermap = {};
membermap.fn = {};
membermap.geocoded = 0;
membermap.cache = {};
membermap.users = {};
membermap.config = {};

membermap.fn.initialize = function () {
    if (typeof membermap.users != 'object') {
        return;
    }

    document.getElementById('membermap').style.width = membermap.config.width;
    document.getElementById('membermap').style.height = membermap.config.height + 'px';

    membermap.google = {};
    membermap.google.options = {
        zoom: membermap.config.zoom,
        mapTypeId: google.maps.MapTypeId[membermap.config.type],
        center: new google.maps.LatLng(membermap.config.lat, membermap.config.lng)
    };

    membermap.google.map = new google.maps.Map(document.getElementById('membermap'), membermap.google.options);
    membermap.google.geocoder = new google.maps.Geocoder();
    membermap.google.bounds = new google.maps.LatLngBounds();

    if (membermap.config.cluster) {
        membermap.google.cluster = new MarkerClusterer(membermap.google.map);
    }

    google.maps.event.addListenerOnce(membermap.google.map, 'idle', membermap.fn.geocode);

    if (membermap.config.delay) {
        membermap.intval = window.setInterval(membermap.fn.markers, membermap.config.delay);
    }
}

membermap.fn.markers = function () {
    var finished = true;
    for (var user = 0; user < membermap.users.length; user++) {
        if (!membermap.users[user].placed && membermap.users[user].ready) {
            membermap.users[user].placed = true;
            membermap.fn.marker(user);
            finished = false;
            break;
        }

        if (!membermap.users[user].ready) {
            finished = false;
        }
    }

    if (finished) {
        window.clearInterval(membermap.intval);
    }
}

membermap.fn.marker = function (user) {
    if (typeof membermap.users[user].position == 'undefined') {
        return;
    }

    membermap.users[user].marker = new google.maps.Marker({
        title: membermap.users[user].name,
        position: membermap.users[user].position,
        animation: membermap.config.drop ? google.maps.Animation.DROP : null
    });

    google.maps.event.addListener(membermap.users[user].marker, 'click', function () {
        window.location.href = membermap.users[user].url;
    });

    google.maps.event.addListener(membermap.users[user].marker, 'mouseover', function () {
        this.setZIndex(1000);
    });

    if (membermap.users[user].avatar) {
        membermap.users[user].marker.setIcon(new google.maps.MarkerImage(membermap.users[user].avatar, null, null, null, new google.maps.Size(membermap.config.size, membermap.config.size)));
    }

    if (membermap.config.legend) {
        membermap.fn.legend(user);
    }

    if (membermap.config.cluster) {
        membermap.google.cluster.addMarker(membermap.users[user].marker);
    } else {
        membermap.users[user].marker.setMap(membermap.google.map);
    }

    membermap.geocoded++;

    membermap.google.bounds.extend(membermap.users[user].position);

    if (membermap.geocoded >= membermap.config.center) {
        membermap.google.map.fitBounds(membermap.google.bounds);
    }
}

membermap.fn.legend = function (user) {
    if (!membermap.legend) {
        membermap.legend = document.createElement('div');
        membermap.legend.setAttribute('id', 'membermap_legend');
        membermap.legend.setAttribute('class', 'gm-style-mtc');
        membermap.google.map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(membermap.legend);
    }

    var row = document.createElement('div');
    row.innerHTML = membermap.users[user].name;

    google.maps.event.addDomListener(row, 'mouseover', function () {
        this.style.fontWeight = 'bold';
        membermap.users[user].marker.setZIndex(1000);
        membermap.users[user].marker.setAnimation(google.maps.Animation.BOUNCE);
    });

    google.maps.event.addDomListener(row, 'mouseout', function () {
        this.style.fontWeight = 'normal';
        membermap.users[user].marker.setAnimation(null);
    });

    google.maps.event.addDomListener(row, 'click', function () {
        var position = membermap.users[user].marker.getPosition();
        membermap.google.map.panTo(position);
        membermap.google.map.setZoom(10);
    });

    document.getElementById('membermap_legend').appendChild(row);
}

membermap.fn.geocode = function () {
    for (var user = 0; user < membermap.users.length; user++) {
        if (!membermap.users[user].ready) {
            membermap.users[user].requests++;
            var position;
            if (position = membermap.cache.get(membermap.users[user].address)) {
                membermap.users[user].position = new google.maps.LatLng(position.k, position.A);
                membermap.users[user].ready = true;
                if (!membermap.config.delay) {
                    membermap.fn.marker(user);
                }
            } else {
                membermap.google.geocoder.geocode({'address': membermap.users[user].address}, function (user) {
                    return(function (results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            membermap.users[user].position = results[0].geometry.location;
                            membermap.users[user].ready = true;
                            membermap.cache.set(membermap.users[user].address, membermap.users[user].position);
                            if (!membermap.config.delay) {
                                membermap.fn.marker(user);
                            }
                        } else {
                            window.setTimeout(membermap.fn.geocode, 500 * membermap.users[user].requests);
                            if (membermap.users[user].requests >= membermap.config.requests) {
                                membermap.users[user].ready = true;
                            }
                        }
                    });
                }(user));
            }
        }
    }
}

membermap.cache.set = function (key, val) {
    if (!window.localStorage) {
        return false;
    }

    return localStorage.setItem('membermap_' + key, JSON.stringify(val));
}

membermap.cache.get = function (key, val) {
    if (!window.localStorage) {
        return false;
    }

    if (val = localStorage.getItem('membermap_' + key)) {
        return JSON.parse(val);
    }

    return val;
}

google.maps.event.addDomListener(window, 'load', membermap.fn.initialize);