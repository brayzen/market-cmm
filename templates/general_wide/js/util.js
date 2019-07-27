
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.7.2
 *  LICENSE: FL973Z7CTGV5 - http://www.flynax.com/license-agreement.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: market.coachmatchme.com
 *  FILE: UTIL.JS
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

var flUtilClass = function(){
    /**
     * Last ajax request
     * 
     * @type object
     */
    this.ajaxRequest = null;

    /**
     * Last ajax request key
     * 
     * @type String
     */
    this.ajaxKey = null;

    /**
     * Media points data
     * 
     * @type Array
     */
    this.media_points = {
        all_tablet_mobile: 'screen and (max-width: 991px)'
    };

    /**
     * Delay of slow internet
     * @since 4.7.0
     * @type  {Number}
     */
    this.loadingDelay = 300;

    /**
     * Initial class method
     * 
     */
    this.init = function() {
        this.markLoadedScripts();
    };

    /**
     * Mark all document loaded scripts to avoid it's repeat 
     * uploading by loadScript method
     */
    this.markLoadedScripts = function() {
        var scripts = document.getElementsByTagName('script');

        for (var i in scripts) {
            if (!scripts[i].src) {
                continue;
            }

            scripts[i].onload = (function(i){
                scripts[i].loaded = true;
            })(i);
        }
    };

    /**
     * Do ajax call
     * 
     * @param array    - ajax request data
     * @param function - callback function
     * @param boolean  - is get request
     */
    this.ajax = function(data, callback, get) {
        // Abort the previous query if it's still in progress
        if (this.ajaxRequest 
            && data.ajaxKey
            && data.ajaxKey == this.ajaxKey
        ) {
            this.ajaxRequest.abort();
        }

        if (!data.mode) {
            console.log('flynax.ajax - no "mode" index in the data parameter found, "mode" is required');
            return;
        }

        if (typeof callback != 'function') {
            console.log('flynax.ajax - the second parameter should be callback function');
            return;
        }

        // request options
        var options = {
            type: get ? 'GET' : 'POST',
            url: rlConfig['ajax_url'],
            data: data,
            dataType: 'json'
        }

        // save move
        this.ajaxKey = data.ajaxKey;

        // process request
        this.ajaxRequest = $.ajax(options)
            .success(function(response, status){
                callback(response, status);
            })
            .fail(function(object, status){
                if (status == 'abort') {
                    return;
                }

                callback(false, status);
            });
    };

    /**
     * Load script(s) once
     * 
     * @param mixed    - script src as string or strings array
     * @param function - callback function
     */
    this.loadScript = function(src, callback){
        var loaderClass = function(){
            var self = this;

            this.completed = false;
            this.urls = [];
            this.done = [];
            this.loaded = [];
            this.callback = function(){};

            this.init = function(src, callback){
                if (!src) {
                    console.log('loadScript Error: no scrip to load specified');
                    return;
                }

                this.urls = typeof src == 'string' ? [src] : src;
                this.callback = typeof callback == 'function'
                    ? callback
                    : this.callback;

                // Fix script url protocol
                this.fixProtocol();

                // Check for already loaded script
                this.checkLoaded();

                // Loads scripts
                for (var i in this.urls) {
                    this.load(this.urls[i], i);
                }

                // Call callback
                this.call();
            }

            this.checkLoaded = function(){
                var loaded_scripts = document.getElementsByTagName('script');

                for (var i in loaded_scripts) {
                    if (typeof loaded_scripts[i] != 'object') {
                        continue;
                    }

                    var index = this.urls.indexOf(loaded_scripts[i]['src']);

                    if (index < 0) {
                        continue;
                    }

                    // Process loaded script
                    this.processLoaded(loaded_scripts[i], index);
                }
            }

            this.processLoaded = function(script, index){
                if (script.loaded) {
                    this.loaded[index] = true;
                    this.done[index] = true;
                } else {
                    var event = script.onload;

                    script.onload = function(){
                        self.done[index] = true;

                        // Call original event
                        if (typeof event == 'function') {
                            event.call();
                        }

                        // Call new event
                        self.call();
                    };
                    this.loaded[index] = true;
                }
            }

            // Check state
            this.isStateReady = function(readyState){
                return (!readyState || $.inArray(readyState, ['loaded', 'complete', 'uninitialized']) >= 0);
            }

            // Load script
            this.load = function(url, i) {
                // Skip loaded
                if (this.loaded[i]) {
                    return;
                }

                // Create script
                var script = document.createElement('script');
                script.src = url;

                // Bind to load events
                script.onload = function(){
                    if (self.isStateReady(script.readyState)) {
                        self.done[i] = true;

                        // Run the callback
                        self.call();

                        // Mark as loaded
                        script.loaded = true;
                    }

                    // Handle memory leak in IE
                    //script.onload = script.onreadystatechange = script.onerror = null; TODO TEST
                };

                // On error callback
                script.onerror = function(){
                    self.callback.call(new Error('Unable to load the script: ' + url));
                };

                // Append script into the head
                var head = document.getElementsByTagName('head')[0];
                head.appendChild(script);

                // Mark as loaded
                this.loaded[i] = true;
            }

            this.isReady = function(){
                var count = 0;
                for (var i in this.done) {
                    if (this.done[i] === true) {
                        count++;
                    }
                }

                return count == this.urls.length;
            }

            this.call = function() {
                if (this.isReady() && !this.completed) {
                    this.callback.call(this);
                    this.completed = true;
                }
            }

            this.fixProtocol = function() {
                if (!location.protocol) {
                    return;
                }

                for (var i in this.urls) {
                    if (0 === this.urls[i].indexOf('//')) {
                        this.urls[i] = location.protocol + this.urls[i];
                    }
                }
            }
        }

        var loader = new loaderClass();
        loader.init(src, callback);
    }

    this.loadStyle = function(src) {
        src = typeof src == 'string' ? [src] : src;

        for (var i in src) {
            $('<link>')
                .attr('rel', 'stylesheet')
                .attr('type', 'text/css')
                .attr('href', src[i])
                .appendTo('head');
        }
    }
}

var flUtil = new flUtilClass();
