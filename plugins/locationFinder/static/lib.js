
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: LIB.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2019 | All copyrights reserved.
 *  
 *  http://www.flynax.com/
 ******************************************************************************/

var locationFinderClass = function(){
    var self = this;

    this.geocodeURL    = 'https://maps.googleapis.com/maps/api/geocode/json?language=en-GB&latlng={latLng}';
    this.option        = [];
    this.map           = null;
    this.geocoder      = new google.maps.Geocoder();
    this.latLng        = new google.maps.LatLng(37.7577627, -122.4726194); // San Francisco, CA
    this.marker        = null;
    this.elem          = null
    this.fromPost      = false;
    this.searchBox     = null;
    this.searchInput   = null;
    this.infoBox       = null;
    this.sync          = false;
    this.multifield    = false;
    this.lastPlaceID   = null;
    this.mappingFields = ['Country', 'State', 'City'];
    this.mfHandler     = false;

    this.init = function(params){
        this.option = params;
        this.elem   = document.getElementById(this.option.mapElementID);
        this.$form  = $(this.elem).closest('form');

        if (params.googleAPIKey) {
            this.geocodeURL += '&key=' + params.googleAPIKey;
        }

        // Get default location from POST
        if (params.postLat !== false && params.postLng !== false) {
            this.latLng = new google.maps.LatLng(params.postLat, params.postLng);
            this.fromPost = true;
        }
        // Take from admin panel settings
        else if (params.defaultLocation.indexOf(',') > 0) {
            var lat_lng = params.defaultLocation.split(',');
            this.latLng = new google.maps.LatLng(lat_lng[0], lat_lng[1]);
        }

        // Redefine zoom from post
        if (params.postZoom !== false) {
            this.option.zoom = params.postZoom;
        }

        // Define synchronization availability
        if (params.mapping && this.fieldsExist()) {
            this.sync = true;
        }

        // Define multifield plugins support
        this.defineMultifield();

        // Show map container
        $(params.containerID).removeClass('hide');

        this.buildMap();
        this.buildSearch();
        this.createMarker();
        this.setLocaton();
        this.setListeners();
    }

    this.destroy = function(){
        $(this.elem).empty();

        this.map         = null;
        this.marker      = null;
        this.lastPlaceID = null;

        // Hide map container
        $(this.option.containerID).addClass('hide');
    }

    this.buildMap = function(){
        var map_options = {
            zoom: this.option.zoom,
            center: this.latLng,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            scrollwheel: false,
            disableDoubleClickZoom: true
        }
        this.map = new google.maps.Map(
            this.elem,
            map_options
        );
    }

    this.buildSearch = function(){
        this.searchInput = $('<input>')
            .attr('type', 'text')
            .attr('placeholder', lang['locationFinder_address_hint'])
            .attr('autocomplete', false)
            .attr('form', 'exclude') // Exclude the field from the entrire form
            .addClass('lf-location-search')
            .get(0);
        this.searchBox = new google.maps.places.SearchBox(this.searchInput);
        this.map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(this.searchInput);
    }

    this.setListeners = function(){
        // Location search listener
        this.searchBox.addListener('places_changed', function(){
            var place = self.searchBox.getPlaces()[0];
            
            if (place.geometry) {
                self.latLng = place.geometry.location;
                self.updateLocation();
            }

            self.infoBox.close();
        });

        // Map dragend listener
        google.maps.event.addListener(this.marker, 'dragstart', function(){
            self.infoBox.close();
        });
        google.maps.event.addListener(this.marker, 'dragend', function(){
            self.update();
        });
        google.maps.event.addListener(this.map, 'zoom_changed', function(){
            self.update();
        });
        google.maps.event.addListener(this.map, 'dblclick', function(event){
            self.latLng = event.latLng;
            self.marker.setPosition(event.latLng);

            self.update();
        });

        // Search input listener for IE
        if (navigator.userAgent.match(/Trident\/7\./)) {
            $(this.elem).keydown(function(event){
                if (event.keyCode == 13) {
                    self.$form.attr('onsubmit', 'return false;');
                }
            }).focusout(function(){
                self.$form.removeAttr('onsubmit');
            });
        }
    }

    this.createMarker = function(){
        this.marker = new google.maps.Marker({
            position: this.latLng,
            map: this.map,
            draggable: true
        });

        this.infoBox = new google.maps.InfoWindow({
            content: lang['locationFinder_drag_notice'],
            maxWidth: 300
        });

        google.maps.event.addListener(this.marker, 'click', function(){
            self.infoBox.open(self.map, self.marker);
        });

        // Open infoBox on default state
        if (!this.fromPost) {
            this.infoBox.open(this.map, this.marker);
        }
    }

    this.setLocaton = function(){
        // Don't update map location in edit mode
        if (this.fromPost) {
            return;
        }

        // Set location with help of browser 
        if (this.option.useVisitorLocation) {
            if (navigator.permissions) {
                navigator.permissions
                    .query({name: 'geolocation'})
                    .then(function(result) {
                        if (['granted', 'prompt'].indexOf(result.state) >= 0) {
                            self.setLocationFromNavigator();
                        } else {
                            self.setLocationByIP();
                        }
                    });
            }
        } else {
            this.update();
        }
    }

    this.setLocationFromNavigator = function(){
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position){
                self.latLng = new google.maps.LatLng(
                    position.coords.latitude,
                    position.coords.longitude
                );
                self.updateLocation();
            }), function(){
                self.setLocationByIP();
            };
        }
    }

    this.setLocationByIP = function(){
        if (!this.option.ipLocation || !this.option.ipLocation.replace(/[,\s]+/g, '').length) {
            this.update();
            return;
        }

        this.geocoder.geocode({address: this.option.ipLocation}, function(results, status){
            if (status == google.maps.GeocoderStatus.OK) {
                self.latLng = results[0].geometry.location;
                self.updateLocation();
            }
        });
    }

    /**
     * Update map position
     *
     * @since 4.0.3 - noSync parameter added
     * @param  bool noSync - prevent synchronization
     */
    this.updateLocation = function(noSync){
        this.marker.setPosition(this.latLng);
        this.map.setCenter(this.latLng);

        this.update(noSync);
    }

    /**
     * Update location related data and synchronize fields location with map
     *
     * @since 4.0.3 - noSync parameter added
     * @param  bool noSync - prevent synchronization
     */
    this.update = function(noSync){
        // Update location data
        $('#lf_zoom').val(this.map.getZoom());
        $('#lf_lat').val(this.marker.getPosition().lat());
        $('#lf_lng').val(this.marker.getPosition().lng());

        if (noSync) {
            return;
        }

        // Synchronise the map lication with dropdowns
        this.synchronise();
    }

    this.synchronise = function(){
        if (!this.sync) {
            return;
        }

        if (this.multifield) {
            this.multifieldSync();
        } else {
            this.staticSync();
        }
    }

    this.multifieldSync = function(){
        var field = this.option['mappingCountry'];

        this.getComponents(this.marker.getPosition(), function(components){
            var place_id_city = null;
            var place_id_neighborhood = null;

            components.forEach(function(item){
                switch (item.types[0]) {
                    case 'locality':
                        place_id_city = item.place_id;
                        break;

                    case 'neighborhood':
                        place_id_neighborhood = item.place_id;
                        break;
                }
            })

            var place_id = self.option.useNeighborhood && place_id_neighborhood
                ? place_id_neighborhood
                : place_id_city;

            if (place_id && self.lastPlaceID != place_id) {
                // Save the latest place ID
                self.lastPlaceID = place_id;

                // Get format key by place ID
                var data = {
                    mode: 'locationFinder',
                    cityPlaceID: place_id_city,
                    neighborhoodPlaceID: place_id_neighborhood,
                };
                flUtil.ajax(data, function(response, status){
                    if (response.status == 'OK' && response.results) {
                        mfFieldVals = {};

                        for (var item in mfFields) {
                            mfFieldVals[mfFields[item]] = response.results.keys[item];

                            /**
                             * Fix multifield multiple onChange event binding
                             */
                            if (!self.mfHandler) {
                                self.$form.find('select[name="f[' + mfFields[item] + ']"]')
                                    .val(0)
                                    .off('change');
                            }
                        }

                        if (self.mfHandler) {
                            self.mfHandler.values = mfFieldVals;
                            self.$form.find('select[name="f[' + mfFields[0] + ']"]')
                                .val(response.results.keys[0])
                                .trigger('change');
                        } else {
                            self.mfHandler = new mfHandlerClass();
                            self.mfHandler.init(mf_prefix, mfFields, mfFieldVals);
                        }
                    }
                });
            }
        });
    }

    this.staticSync = function(){
        this.getComponents(this.marker.getPosition(), function(components){
            var data = {};

            for (var i in components) {
                switch (components[i].types[0]) {
                    case 'locality':
                        data.City = components[i].address_components[0].long_name;
                        break;

                    case 'administrative_area_level_1':
                        data.State = components[i].address_components[0].long_name;;
                        break;

                    case 'country':
                        data.Country = components[i].address_components[0].long_name;;
                        break;
                }
            }

            self.mappingFields.forEach(function(field){
                self.setValue(field, data[field]);
            });
        });
    }

    this.backSync = function(target){
        var address = [];

        for (var i in this.mappingFields) {
            $field = this.getField(this.mappingFields[i]);

            var value = $field.prop('tagName').toLowerCase() == 'select'
                ? $field.find('option:selected').text()
                : $field.val();

            address.push(value);

            if ($field.get(0) == $(target).get(0)) {
                break;
            }
        };

        var query = address.reverse().join(', ');

        // Set the address to the search input
        $(this.searchInput).val(query);

        // Move map to the address
        this.geocoder.geocode({address: query}, function(results, status){
            if (status == google.maps.GeocoderStatus.OK) {
                self.latLng = results[0].geometry.location;
                self.updateLocation(true);
            }
        });
    }

    this.onChangeListener = function(name){
        var $field = self.getField(name);

        $field.change(function(e){
            $(this)[
                !$(this).val() || $(this).val() === '0'
                    ? 'removeClass'
                    : 'addClass'
            ]('affected');

            // Synchronise the map with location from fields
            if (e.originalEvent) {
                self.backSync(this);
            }
        });
    }

    this.setValue = function(name, value){
        var $field = self.getField(name);

        if ($field.hasClass('affected')) {
            return false;
        }

        if ($field.prop('tagName').toLowerCase() == 'select') {
            $field.find('> option').filter(function(){
                if ($(this).text() == value) {
                    $field.val($(this).val());
                    return false;
                }
            });
        } else {
            $field.val(value);
        }
    }

    this.getField = function(name){
        var field = this.option['mapping' + name];
        return this.$form.find('*[name^="f[' + field + ']"]');
    }

    this.getComponents = function(position, callback){
        var url = this.geocodeURL.replace('{latLng}', position.lat() + ',' + position.lng());

        $.getJSON(url, function(response) {
            if (response.status == 'OK') {
                callback.call(this, response.results);
            }
        }).fail(function(response) {
            self.error('getComponents() failed: ' + response.responseJSON.error_message);
        });
    }

    this.error = function(message){
        console.log('locationFinder: ' + message);
    }

    this.fieldsExist = function(){
        var exist = false;

        this.mappingFields.forEach(function(field, index){
            // Set field on change listener
            if (self.isFieldAvailable(field)) {
                exist = true;
                self.onChangeListener(field);
            }
            // Remove unavailable field
            else {
                self.mappingFields.splice(index, 1);
            }
        });

        return exist;
    }

    this.isFieldAvailable = function(name){
        var field = this.option['mapping' + name];
        var exists = false;

        if (field) {
            exists = !!this.$form.find('*[name^="f[' + field + ']"]').length;
        }

        return exists;
    }

    this.defineMultifield = function(){
        var field = this.option['mappingCountry'];

        if (typeof mfHandlerClass == 'function'
            && this.$form.find('select[name^="f[' + field + '_level"]').length
            && typeof mfFields == 'object'
        ) {
            this.multifield = true;
        }
    }
}
