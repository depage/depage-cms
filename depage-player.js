    /**
 * @require framework/shared/jquery-1.4.2.js
 * @require framework/shared/depage-jquery-plugins/depage-flash.js
 * @require framework/shared/depage-jquery-plugins/depage-browser.js
 * 
 * @file depage-player.js
 * 
 * Adds a custom video player, using either HTML5 video if available, or falling back to flash if not.
 * 
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depagecms.net]
 * 
 * @author Ben Wallis
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    }
    
    // shiv {{{ 
    /**
     * Shiv Video
     * 
     * Adds video element to the DOM to enable recognition in IE < 9.
     * 
     * @return void
     */
    if ($.browser.msie && $.browser.version < 9) {
        $('head').append('<style>video{display:inline-block;*display:inline;*zoom:1}</style>');
        document.createElement("video");
        document.createElement("source");
    }
    // }}}
    
    
    /**
     * Depage Player
     * 
     * @param el
     * @param index - player index
     * @param options
     * 
     * @return context
     */
    $.depage.player = function(el, index, options){
        // {{{ variables
        // To avoid scope issues, use 'base' instead of 'this' to reference this
        // class from internal events and functions.
        var base = this;
        
        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        // Add a reverse reference to the DOM object
        base.$el.data("depage.player", base);
        
        // cache selectors
        var video = $('video', base.$el)[0];
        var $video = $(video);
        var $indicator = $("a.indicator", base.$el);
        
        var $wrapper = null;
        
        var duration = video.currentTime || $("a", base.$el).attr("data-video-duration");
        var playing = false;
        
        // use the build-in controls for iPhone and iPad
        var useCustomControls = !$.browser.iphone && !$.browser.ipad;
        
        // set the player mode - 'html5' / 'flash' / false (fallback)
        var mode = false;
        
        // variable used to save element styles when using the fallback fullscreen
        var styles_cache = null;
        
        // cache window selector
        var $window = $(window); 
        
        // cache the control selectors
        base.controls = {};
        // }}}
        
        // {{{ init()
        /**
         * Init
         * 
         * Setup the options.
         * 
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.player.defaultOptions, options);
            
            base.options.width = base.options.width || base.$el.width();
            base.options.height = base.options.height || base.$el.height();
            base.options.playerId = base.options.playerId + index;
            
            $.depage.player.instances[base.options.playerId] = base;
            
            // listen to key events
            $(document).bind('keypress', function(e){
                if ($(document.activeElement).is(':input')){
                    // continue only if an input is not the focus
                    return true;
                }
                switch (parseInt(e.which || e.keyCode)) {
                    case 32 : // spacebar
                        if (playing) {
                            base.player.pause();
                        } else {
                            base.player.play();
                        }
                        e.preventDefault();
                        break;
                    case 102 : // 'f' key
                        base.player.fullscreen();
                        e.preventDefault();
                        break;
                }
            });
            
            // listen to keydown events for cursors (n.b. keydown for chrome / ie support - http://code.google.com/p/chromium/issues/detail?id=2606)
            $(document).bind('keydown', function(e){
                if ($(document.activeElement).is(':input')){
                    // continue only if an input is not the focus
                    return true;
                }
                switch (parseInt(e.which || e.keyCode)) {
                    case 39 : // cursor right
                        base.player.seek(base.player.currentTime + 10);
                        e.preventDefault();
                        break;
                    case 37 : // cursor left
                        base.player.seek(base.player.currentTime - 9);
                        e.preventDefault();
                        break;
                }
            });
        };
        // }}}
        
        // {{{ videoSupport()
        /**
         * Video Support
         * 
         * Checks browser video codec support.
         * 
         * http://www.modernizr.com/
         * 
         * NB IE9 Running on Windows Server SKU can cause an exception to be thrown, bug #224
         * 
         * @returns object
         * 
         */
        base.videoSupport = function (){
            var support = {};
                
            try {
                support.ogg = video.canPlayType('video/ogg; codecs="theora"').replace(/^no$/,'');
                support.h264 = video.canPlayType('video/mp4; codecs="avc1.42E01E"').replace(/^no$/,'');
                support.webm = video.canPlayType('video/webm; codecs="vp8, vorbis"').replace(/^no$/,'');
            } catch(e) { }
            finally{
                // earliest support for flash player with h264
                support.flash = $.depage.flash({requiredVersion:"9,0,115"}).detect();
            }
            
            return support;
        };
        // }}}
        
        // {{{ video()
        /**
         * Video
         * 
         * Entry point to build the video.
         *  - Adds the wrapper div
         *  - Build the controls
         *  - Adds $indicator click handler
         *  - Autoloads
         * 
         * @return void
         */
        base.video = function() {
            var support = base.videoSupport();
            
            // SET TO DEBUG FLASH MODE
            // support = { 'flash' : true }; 
            
            // determine the supported player mode - flash or html5
            if ( support.h264 && $('source:[type="video/mp4"]', video).length > 0
                || support.ogg && $('source:[type="video/ogg"]', video).length > 0
                || support.webm && $('source:[type="video/webm"]', video).length > 0) {
                mode = 'html5';
                base.player = video;
                base.html5.setup();
            } else if (support.flash) {
                 mode = 'flash';
                 base.overlay($video);
                 
                 // setup flash player
                 base.player = { initialized: false };
                 base.flash.transport();
                 
                 // preload
                 var preloadAttr = $video.attr('preload');
                 if (typeof(preloadAttr) !== 'undefined' && preloadAttr == 'true') {
                     base.flash.insertPlayer();
                 }
                 // autoplay
                 var autoplayAttr = $video.attr('autoplay');
                 if (typeof(autoplayAttr) !== 'undefined' && autoplayAttr == 'true') {
                     base.player.play();
                 }
                 
                 // TODO support for loop
            } else {
                // fallback
                return false;
            }
            
            $indicator.click(function() {
                base.player.play();
                return false;
            });
            
            var div = $("<div class=\"controls\"></div>");
            if (useCustomControls) {
                base.addControls(div);
            } else {
                $indicator.remove();
                $video.attr("controls", "true");
            }
            base.addLegend(div);
            div.appendTo(base.$el);
        };
        // }}}
        
        /**
         * Namespace HTML5 funcitons
         */
        base.html5 = {
            // {{{ setup
            /**
             * HTML5 Setup the handlers for the HTML5 video
             * 
             * @return void
             */
            setup : function() {
                // attribute fixes issue with IE9 poster not displaying - add in html
                // $video.attr('preload', 'none'); 
                
                $video.bind("play", function(){
                    base.play();
                });
                
                $video.bind("playing", function(){
                    // @TODO commented because this was firing base.play() twice ?
                    // @TODO uncommented because of problems in firefox when video finished playing
                    base.play();
                });
                
                $video.bind("pause", function(){
                    base.pause();
                });
                
                $video.bind("durationchange", function(){
                    base.duration(this.duration);
                });
                
                $video.bind("timeupdate", function(){
                    base.setCurrentTime(this.currentTime);
                });
                
                $video.bind("ended", function(){
                    base.end();
                });
                
                /**
                 * HTML5 Progress Event
                 * 
                 * Fired when buffering
                 * 
                 * TODO doesn't always seem to fire?
                 * 
                 * @return false
                 */
                $video.bind("progress", function(){
                    var defer = null;
                    var progress = function(){
                        var loaded = 0;
                        if (video.buffered && video.buffered.length > 0 && video.buffered.end && video.duration) {
                            loaded = video.buffered.end(video.buffered.length-1) / video.duration;
                        } 
                        // for browsers not supporting buffered.end (e.g., FF3.6 and Safari 5)
                        else if (typeof(video.bytesTotal) !== 'undefined' && video.bytesTotal > 0
                                && typeof(video.bufferedBytes) !== 'undefined') {
                            loaded = video.bufferedBytes / video.bytesTotal;
                        }
                        
                        base.percentLoaded(loaded);
                        
                        // last progress event not fired in all browsers
                        if ( !defer && loaded < 1 ) {
                            defer = setInterval(function(){ progress(); }, 1500);
                        }
                        else if (loaded >= 1) {
                             clearInterval(defer);
                        }
                    };
                    
                    progress();
                });
                
                /**
                 * HTML5 Loaded Data Event
                 * 
                 * Fired when the player is fully loaded
                 * 
                 * TODO doesn't always seem to fire?
                 * 
                 * @return false
                 */
                $video.bind("loadeddata", function(){
                    base.percentLoaded(1);
                });
                
                /**
                 * HTML5 Waiting Event
                 * 
                 * Fired when the player stops becasue the next frame is not buffered.
                 * 
                 * Display a buffering image if available.
                 * 
                 * @return void
                 */
                $video.bind("waiting", function(){
                    base.html5.$buffer_image.show();
                });
                
                /**
                 * HTML5 Playing Event
                 * 
                 * Fired when the playback starts after pausing or buffering.
                 * 
                 * Clear the buffering image.
                 * 
                 * @return void
                 */
                $video.bind("playing", function(){
                    base.html5.$buffer_image.hide();
                });
                
                // resize
                if (useCustomControls && (base.options.width != video.videoWidth || base.options.height != video.videoHeight)) {
                     var height = base.options.height || base.$el.height();
                     var width = base.options.width || base.$el.width();
                     base.resize(width, height);
                }
                
                /**
                 * HTML5 Seek
                 * 
                 * Create a seek method for the html5 player
                 * 
                 * @param offset - current time;
                 * 
                 * @return false
                 */
                base.player.seek = function(offset){
                    if (offset <= 0) {
                        offset = 0.1;
                    }
                    if (offset > duration) {
                        offset = duration;
                    }
                    base.player.currentTime = offset;
                    return false;
                };
                
                /**
                 * HTML5 Fullscreen
                 * 
                 * Create a fullscreen method for the html5 player
                 * 
                 * @return false
                 */
                base.player.fullscreen = function(){
                    // start playback on fullscreen
                    base.play();
                    base.player.play();
                    
                    var native_fullscreen_support = false;
                    
                    if (true){ // SET FALSE TO DEBUG FULLSCREEN FALLBACK
                        if (typeof(base.player.webkitEnterFullScreen) !== 'undefined') {
                            // TODO http://stackoverflow.com/questions/7226114/dom-exception-11-when-calling-webkitenterfullscreen
                             base.player.webkitEnterFullScreen();
                             native_fullscreen_support = true;
                        } else if (typeof(base.player.webkitRequestFullScreen) !== 'undefined') {
                            // webkit (works in safari and chrome canary)
                            base.player.webkitRequestFullScreen();
                            native_fullscreen_support = true;
                        } else if (typeof(base.player.mozRequestFullScreen) !== 'undefined'){
                            // firefox (works in nightly)
                            base.player.mozRequestFullScreen();
                            native_fullscreen_support = true;
                        } else if (typeof(base.player.requestFullscreen) !== 'undefined') {
                            // w3c proposal
                            base.player.requestFullscreen();
                            native_fullscreen_support = true;
                        }
                    }
                    
                    if (!native_fullscreen_support) {
                        // fallback
                        base.fullscreen();
                    }
                    
                    // make sure the native player controls are displayed when going full screen (n.b. not automatic in firefox)
                    if (native_fullscreen_support) {
                        $video.attr('controls', 'controls');
                        var is_fullscreen = true;
                        // bind to fullscreenchange event and clear up controls if exiting...
                        $(document).bind('fullscreenchange mozfullscreenchange webkitfullscreenchange', function(e) {
                            switch(e.type){
                                case 'fullscreenchange' :
                                    is_fullscreen = document.fullscreen;
                                    break;
                                case 'mozfullscreenchange' :
                                    is_fullscreen = document.mozFullScreen;
                                    break;
                                case 'webkitfullscreenchange' :
                                    is_fullscreen = document.webkitIsFullScreen;
                                    break;
                            }
                            if (!is_fullscreen) {
                                $video.removeAttr('controls');
                            }
                        });
                    }
                    
                    // trigger onfullscreen event
                    base.options.onFullscreen && base.options.onFullscreen();
                    
                    return false;
                };
                
                /**
                 * HTML5 Exit Fullscreen
                 * 
                 * Exit fullscreen method for html5 - called manually
                 * 
                 * @return false
                 */
                base.player.exitfullscreen = function(){
                    if (typeof(base.player.webkitExitFullScreen) !== 'undefined') {
                        base.player.webkitExitFullScreen();
                    } else if (typeof(document.webkitCancelFullScreen) !== 'undefined') {
                        // webkit (works in safari and chrome canary)
                        document.webkitCancelFullScreen();
                    } else if (typeof(document.mozCancelFullScreen) !== 'undefined'){
                        // firefox (works in nightly)
                        document.mozCancelFullScreen();
                    } else if (typeof(document.cancelFullScreen) !== 'undefined') {
                        // mozilla proposal
                        document.cancelFullScreen();
                    }
                    
                    $video.removeAttr('controls');
                    base.options.onExitFullscreen && base.options.onExitFullscreen();
                    return false;
                };
                
                /**
                 * Bind to resize
                 */
                $(window).resize(base.resize);
            }
            // }}}
        };
        
        /**
         * Namespace Flash Functions
         */
        base.flash = {
            // flash.transport() {{{
            /**
             * Flash Transport
             * 
             * Adds transport actions to the flash player.
             * 
             * Uses set interval to wait for flash initialization.
             * 
             * @return void
             */
            transport : function() {
                
                var actions = [ "load", "play", "pause", "seek" ];
                
                // {{{ serialize()
                /**
                 * serialize
                 * 
                 * Serializes the arguments passed to the flash player object
                 * 
                 * @param string action - control action called
                 * @param array args - flash player arguments
                 * 
                 * @return string code
                 */ 
                var serialize = function ( action, args ){
                    
                    $.each(args, function(i, arg){
                        args[i] = '"' + String(arg).replace('"', '\"') + '"';
                    });
                   
                    return action + "(" + args.join(',') + ");";
                };
                // }}}
                
                $.each ( actions, function (i, action) {
                    
                    base.player[action] = function() {
                        
                        if (!base.player.initialized) {
                            base.flash.insertPlayer();
                        }
                        
                        var code = serialize(action, Array.prototype.slice.call(arguments));
                        
                        var defer = setInterval(function() {
                            caller();
                        }, 300);
                        
                        var caller = function() {
                            try {
                                if (($.browser.msie && eval("window['" + base.options.playerId + "'].f" + code))
                                         || eval("document['" + base.options.playerId + "'].f" + code)){
                                     clearInterval(defer);
                                }
                            } catch (e) { }
                        };
                    };
                });
                
                /**
                 * Fullscreen
                 * 
                 * Add a custom fullscreen acion for the flash player
                 * 
                 * TODO disabled in favour of native full screen flash
                 * 
                 * @return void
                 */
                base.player.fullscreen = function() {
                    // DISABLE FULLSCREEN FLASH
                    return false;
                    
                    /*
                    if (!base.player.initialized) {
                        base.flash.insertPlayer();
                    }
                    base.fullscreen();
                    */
                };
            },
            // }}}
            
            // {{{ flash.insertPlayer()
            /**
             * Insert Flash Player
             * 
             * Insert the flash object for the player using the depage flash plugin.
             * 
             * Overwrites the video and placeholder image.
             * 
             * @return void
             */
            insertPlayer : function() {
                // get absolute url from source attribute with mp4-type
                var $link = $("<a href=\"" + $('source:[type="video/mp4"]', video).attr("src") + "\"></a>").appendTo("body");
                var url = $link[0].toString();
                $link.remove();
                
                var flashParams = {
                    rand: Math.random(),
                    id : base.options.playerId
                };
                if (window.console && base.options.debug) {
                    flashParams.debug = "true";
                }
                var html = $.depage.flash().build({
                    src    : base.options.assetPath + "depage_player.swf",
                    // TODO needs to fit screen for resize
                    //width  : base.options.width,
                    //height : base.options.height,
                    id     : base.options.playerId,
                    wmode  : 'transparent',
                    params : flashParams
                });
                
                // use innerHTML for IE < 9 otherwise player breaks!!
                $wrapper[0].innerHTML = html.plainhtml;
                
                base.player.initialized = true;
                
                base.player.load(url);
            }
            // }}}
        };
            
        // {{{ resize()
        /**
         * Resize Player
         * 
         * Attempt to resize the player to the given dimensions.
         * 
         * Will constrain or crop according to the base.options.
         * 
         * @return void
         */
        base.resize = function(toWidth, toHeight) {
            // get the player object
            var $player = mode==='flash'
                ? $('object', base.$el)
                : $video;
            
            // note that if the ready state is 0 (when not preloaded we do not have dimensions)
            // fallback to element dom attributes
            var ratio = mode==='flash' || $player[0].readyState === 0
                ? $player[0].width / $player[0].height
                : $player[0].videoWidth / $player[0].videoHeight;
            
            // scale to outer div maintain constraints
            if (base.options.constrain && !isNaN(ratio)) {
                if (toWidth / toHeight < ratio) {
                    toWidth = Math.ceil(ratio * toHeight);
                } else {
                    toHeight = Math.ceil(toWidth / ratio);
                }
            }
            
            // crop to wrapper
            if (mode==='html5' && base.options.crop){
                
                var cropWidth = base.$el.width();
                var cropHeight = base.$el.height();
                
                if (cropWidth && cropHeight) {
                    
                    base.overlay($player, cropWidth, cropHeight);
                    
                    // center video
                    $player
                       .css({ 
                            position: 'relative',
                            left: (cropWidth - toWidth) / 2,
                            top: (cropHeight - toHeight) / 2
                    });
                }
            }
            
            if (mode === 'flash') {
                // resize by scaling wrapper
                $wrapper
                    .width(toWidth)
                    .height(toHeight);
            } else {
                $player[0].width = toWidth;
                $player[0].height = toHeight;
            }
            
            if (playing) {
                base.player.play();
            }
        };
        // }}}
        
        // {{{ Overlay()
        /**
         * Overlay
         * 
         * Adds an overlay container if not present.
         * Adds inline styling where provided.
         * Used to crop the video or wrap the flash player.
         * 
         * @param $wrap - selector to wrap
         * @param width - width of overlay
         * @param height - height of overlay
         * @param overflow - overflow of overlay
         * 
         * @return void
         */
        base.overlay = function($wrap, width, height, overflow) {
            var style = {};
            
            if (width) {
                style.width = width + 'px';
            }
            
            if (height) {
                style.height = height + 'px'; 
            }
            
            if (true || overflow) {
                style.overflow  = 'hidden';
            };
            
            if (!$wrapper) {
                $wrap.wrap('<div class="wrapper" />');
                $wrapper = $('.wrapper', base.el); // cache after dom append for IE < 9 ?!
            }
            
            if (!$.isEmptyObject(style)) {
                $wrapper.css(style);
            }
        };
        // }}}
        
        // {{{ fullscreen()
        /**
         * Fullscreen
         * 
         * FALLBACK
         * 
         * Custom full screen method implemented by the flash player,
         * and as a fallback for HTML5 video where not browser supported.
         * 
         * NB Deprecated Flash fullscreen mode
         * 
         * @return void
         */
        base.fullscreen = function () {
            var $body = $('body');
            var $controls = $('.controls', base.$el);
            var $button = $('.fullscreen', base.$el);
            
            var $background = $('#fullscreen-background');
            if (!$background.length) {
                $background = $('<div id="fullscreen-background" />').css({
                    'z-index' : '1001',
                    'width' : screen.width,
                    'height' : screen.height,
                    'position' : 'fixed',
                    'top' : '0px',
                    'left' : '0px',
                    'background-color' : '#fff'
                });
                $body.prepend($background);
            }
            
            // save original css attributes if none cached
            // nb - this will be set if already fullscreen and resizing
            styles_cache = styles_cache || {
                'body' : $body.attr('style'),
                'controls' : $controls.attr('style'),
                'el' : base.$el.attr('style')
            };
            
            // set screen position to top corner
            window.scrollTo(0, 0);
            
            // set body css
            $body.css( {
                'margin':0,
                'padding':0,
                // body overflow in FF causes flash to reframe
                'overflow': mode==='html5' ? 'hidden' : ''
            });
            
            // get screen dimensions
            var screenWidth = $window.width();
            var screenHeight = $window.height();
            
            // resize container and position absolutely
            base.$el
                .css({
                    'z-index' : '1002',
                    'position' : 'fixed',
                    'top' : 0,
                    'left' : 0,
                    'width' : screenWidth,
                    'height' : screenHeight
                });
            
            // resize video
            base.resize(screenWidth, screenHeight);
            
            // reposition controls
            $controls
                .css({
                    'position' : 'absolute',
                    'bottom' : '0px',
                    'width' : '100%',
                    'background-color': '#fff'
                })
                // animate the controls on hover
                // TODO 
                /*
                .bind('mouseover.fullscreen', function() {
                    $this = $(this);
                    $this.stop();
                    $this.animate({
                        'opactity': '1',
                        'filter': 'alpha(opacity=1)' // IE < 8
                    });
                })*/ ;
            
            // listen to the escape key
            $(document).bind('keyup.fullscreen', function(e){
                var key = e.which || e.keyCode;
                if (key == 27) {
                    exitFullscreen();
                    $(document).unbind('keyup.fullscreen');
                }
            });
            
            // change button click handler behaviour to exit fullscreen
            $button
                .unbind('click')
                .click(function(){
                    exitFullscreen();
                });
            
            var resize_timeout = null;
            // bind to window resize event and re-init fullscreen
            $window.bind('resize.fullscreen', function(){
                clearTimeout(resize_timeout); // nb - some browsers file resize contiously wait 500ms
                resize_timeout = setTimeout(function() {
                    base.fullscreen(styles_cache);
                }, 500);
            });
            
            // {{{
            /**
             * Exit Fullscreen
             * 
             * FALLBACK for non native fullscreen support
             * 
             * @param styles_chache - {controls: {...}, body {...}} - styles to apply (or restore) to elements on exit
             * 
             * @return void
             */
            var exitFullscreen = function() {
                // remove styles
                $body.removeAttr('style');
                $controls.removeAttr('style');
                base.$el.removeAttr('style');
                
                // remove background
                $background.remove();
                
                // restore cached css attributes
                if (styles_cache) {
                    if (typeof(styles_cache.body) !== 'undefined'){
                         $body.css(styles_cache.body);
                    }
                    
                    if (typeof(styles_cache.controls) !== 'undefined'){
                         $controls.css(styles_cache.controls);
                    }
                    
                    if (typeof(styles_cache.el) !== 'undefined'){
                         base.$el.css(styles_cache.el);
                    }
                }
                
                // clear styles cache (prevents issues with resizing)
                styles_cache = null;
                
                // make sure opacity is restored
                // TODO animate fade in / out of controls
                /*
                $controls.css({
                    'opactity': '0',
                    'filter': 'alpha(opacity=0)' // IE < 8
                });
                */
                
                // resize video
                base.resize(base.$el.width(), base.$el.height());
                
                // unbind control animations
                // TODO control amimations
                // $controls.unbind('mouseover.fullscreen');
                
                // unbind resize
                $window.unbind('resize.fullscreen');
                
                // restore button click handler
                $button
                    .unbind()
                    .click(function(){
                        base.player.fullscreen();
                    });
                
                base.options.onExitFullscreen && base.options.onExitFullscreen();
            };
            // }}}
        };
        // }}}
        
        // {{{ addLegend()
        /**
         * Add Control and Legend-Wrapper
         * 
         * @return void
         */
        base.addLegend = function(div){
            var requirements = $("p.requirements", base.$el);
            var legend = $("p.legend", base.$el);
            
            $("<p class=\"legend\"><span>" + legend.text() + "</span></p>").appendTo(div);
            
            legend.hide();
            requirements.hide();
            
            return div;
        };
        // }}}
        
        // {{{ addControls()
        /**
         * Add Controls
         * 
         * Adds player controls
         * 
         * @return void
         */
        base.addControls = function(div){
            var imgSuffix = ($.browser.msie && $.browser.version < 7) ? ".gif" : ".png";
            
            $video.removeAttr("controls");
            
            base.controls.progress = $("<span class=\"progress\" />")
                .mouseup(function(e) {
                    var offset = (e.pageX - $(this).offset().left) / $(this).width() * duration;
                    base.player.seek(offset);
                });
            base.controls.buffer = $("<span class=\"buffer\"></span>")
                .appendTo(base.controls.progress);
            
            base.controls.position = $("<span class=\"position\"></span>")
                .appendTo(base.controls.progress)
                .bind('dragstart', function(e) {
                    // mouse drag
                    var $progress = $('.progress');
                    var offset = $progress.offset().left;
                    var width = $progress.width();
                    $(this).bind('drag.seek', function(e) {
                        // TODO not firing in firefox!
                        if (e.pageX > 0) { // TODO HACK last drag event in chrome fires pageX = 0?
                            var position = (e.pageX - offset) / width * duration;
                            // console.log(position);
                            base.player.seek(position);
                        }
                    });
                })
                .bind('dragend', function(e) {
                    // unbind drag
                    $(this).unbind('drag.seek');
                    return false;
                });;
            
            base.controls.progress.appendTo(div);
            
            base.controls.play = $("<a class=\"play\"><img src=\"" + base.options.assetPath + "play_button" + imgSuffix + "\" alt=\"play\"></a>")
                .appendTo(div)
                .click(function() {
                    base.player.play();
                    return false;
                });
            
            // NB fullscreen disabled for flash - should be handled internally by flash object
            if (mode != "flash") {
                base.controls.fullscreen = $("<a class=\"fullscreen\"><img src=\"" + base.options.assetPath + "fullscreen_button" + imgSuffix + "\" alt=\"fullscreen\"></a>")
                    .appendTo(div)
                    .click(function() {
                        base.player.fullscreen();
                        return false;
                });
            }
            
            base.controls.pause = $("<a class=\"pause\" style=\"display: none\"><img src=\"" + base.options.assetPath + "pause_button" + imgSuffix + "\" alt=\"pause\"></a>")
                .appendTo(div)
                .click(function() {
                    base.player.pause();
                    return false;
                });
            
            base.controls.rewind = $("<a class=\"rewind\"><img src=\"" + base.options.assetPath + "rewind_button" + imgSuffix + "\" alt=\"rewind\"></a>")
                .appendTo(div)
                .click(function() {
                    base.player.seek(0.1); // setting to zero breaks iOS 3.2
                    return false;
                });
            
            base.controls.time = $("<span class=\"time\" />");
            
            base.controls.current = $("<span class=\"current\">00:00/</span>")
                .appendTo(base.controls.time);
            
            base.controls.duration = $("<span class=\"duration\">" + base.floatToTime(duration) + "</span>")
                .appendTo(base.controls.time);
            
            base.controls.time.appendTo(div);
            
            if (mode != "flash") {
                base.html5.$buffer_image = $('<img class="buffer-image" />').attr('src', base.options.assetPath + 'buffering_indicator.gif').hide();
                base.$el.append(base.html5.$buffer_image);
            }
        };
        // }}}
        
        // {{{ play()
        /**
         * Play
         * 
         * @return void
         */
        base.play = function() {
            $indicator.hide();
            
            if (mode == 'flash' ) {
                $('#poster', base.$el).hide();
            }
            
            if (useCustomControls){
                base.controls.play.hide();
                base.controls.pause.show();
                base.controls.rewind.show();
            }
            
            base.options.onPlay && base.options.onPlay();
            
            playing = true;
        };
        // }}}
        
        // {{{ pause()
        /**
         * Pause
         * 
         * @return void
         */
        base.pause = function() {
            $indicator.show();
            
            if (useCustomControls){
                base.controls.play.show();
                base.controls.pause.hide();
                base.controls.rewind.show();
            }
            
            base.options.onPause && base.options.onPause();
            
            playing = false;
        };
        // }}}
        
        // {{{ end()
        /**
         * End
         * 
         * @return void
         */
        base.end = function() {
            base.pause();
            base.options.onEnd && base.options.onEnd();
        };
        // }}}
        
        // {{{ setCurrentTime()
        /**
         * Set Current Time
         * 
         * @return void
         */        
        base.setCurrentTime = function(currentTime) {
            base.controls.current.html(base.floatToTime(currentTime) + "/");
            base.controls.position.width(Math.min(currentTime / duration * 100, 100) + "%");
        };
        // }}}
        
        // {{{ percentLoaded()
        /**
         * Percent Loaded
         * 
         * 
         * @param percentLoaded
         * 
         * @return void
         */
        base.percentLoaded = function(percentLoaded){
            base.controls.buffer.width(Math.min(percentLoaded * 100, 100) + "%");
        };
        // }}}
        
        // {{{ duration()
        /**
         * Duration 
         * 
         * @param duration
         * 
         */
        base.duration = function(duration) {
            base.controls.duration.html(base.floatToTime(duration));
        };
        // }}}
        
        // {{{ floatToTime() 
        /**
         * FloatToTime
         * 
         * Converts to a float time to a string for display
         * 
         * @param value
         * 
         * @return string - "MM:SS"
         */
        base.floatToTime = function(value) {
            var mins = String("00" + Math.floor(value / 60)).slice(-2);
            var secs = String("00" + Math.floor(value) % 60).slice(-2);
           
            return mins + ":" + secs;
        };
        // }}}
        
        // Run initializer
        base.init();
        
        // Build the video
        base.video();
        
        return base;
    };
    
    // {{{ setPlayerVar()
    /**
     * Flash Set Player Var
     * 
     * This is a callback for the flash player.
     * 
     * @param action
     * @param value
     * 
     * @return void
     */
    $.depage.player.setPlayerVar = function(playerId, action, value) {
        var instance = $.depage.player.instances[playerId];
        
        instance.player[action] = value;
        
        switch (action) {
            case "paused" : 
                if (instance.player.paused){
                    instance.pause();
                } else {
                    instance.play();
                }
                break;
                
            case "currentTime" :
                instance.setCurrentTime(instance.player.currentTime);
                break;
                
            case "percentLoaded" :
                instance.percentLoaded(instance.player.percentLoaded);
                break;
                
            case "duration" :
                instance.duration();
                break;
        }
    };
    // }}}
    
    /**
     * instances
     *
     * Holds all player instances by id
     */
    $.depage.player.instances = [];
    
    var $scriptElement = $("script[src *= '/depage-player.js']");
    var basePath = "";
    if ($scriptElement.length > 0) {
        basePath = $scriptElement[0].src.match(/^.*\//).toString();
    }
    /**
     * Options
     * 
     * @param assetPath - path to the asset-folder (with flash-player and images for buttons)
     * @param playerName - name of the flash swf
     * @param playerId
     * @param width - video width
     * @param height - video height
     * @param crop - crop this video when resizing
     * @param constrain - constrain dimensions of this video when resizing
     * @param debug - if set, the flash player will send console.log messages for his actions
     * @param onPlay - pass callback function to trigger on play event
     * @param onPause - pass callback function to trigger on pause event
     * @param onEnd - pass callback function to trigger on end play event 
     * @param onFullscreen - pass callback function to trigger on entering fullscreen mode.
     * @param onExitFullscreen - pass callback funtion to trigger on exiting fullscreen mode.
     */
    $.depage.player.defaultOptions = {
        assetPath : basePath + "depage_player/",
        playerId : "dpPlayer",
        width : false,
        height : false,
        crop: true,
        constrain: true,
        debug: false,
        onPlay: false,
        onPause: false,
        onEnd: false,
        onFullscreen: false,
        onExitFullscreen: false
    };
    
    $.fn.depagePlayer = function(options){
        return this.each(function(index){
            (new $.depage.player(this, index, options));
        });
    };
    
})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
