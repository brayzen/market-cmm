<!-- admin panle js for realty map template settings -->

<script>
var realty_default_coordinates = '{$config.realty_search_map_location}';
{literal}

$(document).ready(function(){
    var pac_input = $('input[name="post_config[realty_search_map_location_name][value]"][type=text]').get(0);

    if (typeof pac_input == 'undefined')
        return;

    var allow_submit = true;

    $(pac_input).focus(function(){
        allow_submit = false;
    }).blur(function(){
        allow_submit = true;
    });

    // add hidden inputs
    var default_lat = '';
    var default_lng = '';
    if (realty_default_coordinates.indexOf(',') > 0) {
        default_lat = realty_default_coordinates.split(',')[0];
        default_lng = realty_default_coordinates.split(',')[1];
    }

    $(pac_input).after('<input type="hidden" name="search_map_default[lat]" value="'+default_lat+'" />');
    $(pac_input).after('<input type="hidden" name="search_map_default[lng]" value="'+default_lng+'" />');

    // empty selection state simulation
    (function pacSelectFirst(input){
        // store the original event binding function
        var _addEventListener = (input.addEventListener) ? input.addEventListener : input.attachEvent;

        // Simulate a 'down arrow' keypress on hitting 'return' when no pac suggestion is selected,
        // and then trigger the original listener.
        function addEventListenerWrapper(type, listener) {
            if (type == 'keydown') {
                var orig_listener = listener;

                listener = function (event) {
                    var suggestion_selected = $('.pac-item-selected').length > 0;
                    if (event.which == 13 && !suggestion_selected) {
                        var simulated_downarrow = $.Event('keydown', {keyCode: 40, which: 40})
                        orig_listener.apply(input, [simulated_downarrow]);
                    }

                    orig_listener.apply(input, [event]);
                };
            }

            // add the modified listener
            _addEventListener.apply(input, [type, listener]);
        }

        if (input.addEventListener) {
            input.addEventListener = addEventListenerWrapper;
        } else if (input.attachEvent) {
            input.attachEvent = addEventListenerWrapper;
        }

    })(pac_input);

    var autocomplete = new google.maps.places.Autocomplete(pac_input);
    google.maps.event.addListener(autocomplete, 'place_changed', function(res) {
        var place = autocomplete.getPlace();
        var lat, lng = '';

        if (place.geometry) {
            lat = place.geometry.location.lat();
            lng = place.geometry.location.lng();
        } else {
            console.log('realty map settings, geocomplete: geolocation response failed, no geometry');
        }

        $('input[name="search_map_default[lat]"]').val(lat);
        $('input[name="search_map_default[lng]"]').val(lng);
    });

    $(pac_input).closest('form').submit(function(e){
        if (!allow_submit) {
            e.stopPropagation();
            return false;
        }
    });
});

{/literal}
</script>

<!-- admin panle js for realty map template settings end -->