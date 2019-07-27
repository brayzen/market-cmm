<!-- locationFinder javascript on settings page -->

<script>
var locationFinder_default_location = '{$config.locationFinder_default_location}';
lang['locationFinder_set_location_error'] = "{$lang.locationFinder_set_location_error}";
{literal}

$(document).ready(function(){
    // Geolocation autocomplete
    var $input = $('input[name="post_config[locationFinder_search][value]"][type=text]');
    var pac_input = $input.get(0);
    var prev_location = $input.val();

    if (typeof pac_input == 'undefined') {
        return;
    }

    var allow_submit = true;

    $(pac_input).focus(function(){
        allow_submit = false;
    }).blur(function(){
        allow_submit = true;
    });

    // Add hidden inputs
    $(pac_input).after('<input type="hidden" name="locationFinder_default_location" value="'+locationFinder_default_location+'" />');

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

        if (place.geometry) {
            $('input[name=locationFinder_default_location]').val(place.geometry.location.toUrlValue());
        } else {
            $input.val(prev_location);
            printMessage('error', lang['locationFinder_set_location_error']);
        }
    });

    $(pac_input).closest('form').submit(function(e){
        if (!allow_submit) {
            e.stopPropagation();
            return false;
        }
    });

    // Group position
    var field_position = $('select[name="post_config[locationFinder_position][value]"]');
    var field_type = $('input[name="post_config[locationFinder_type][value]"]');
    var field_group = $('select[name="post_config[locationFinder_group][value]"]');

    var locationFinder_check = function(){
        var val = field_position.val();

        if (val == 'in_group') {
            field_type.closest('tr').show();
            field_group.closest('tr').show();
        } else {
            field_type.closest('tr').hide();
            field_group.closest('tr').hide();
        }
    }

    locationFinder_check();
    field_position.change(function(){
        locationFinder_check();
    });
});

{/literal}
</script>

<!-- locationFinder javascript on settings page end -->