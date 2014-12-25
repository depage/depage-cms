/**
 * @require framework/shared/jquery-1.4.2.js
 * @require framework/shared/depage-jquery-plugins/depage-flash.js
 * @require framework/shared/depage-jquery-plugins/depage-browser.js
 *
 * @file depage-audio.js
 *
 * Adds a custom audio player, using either HTML5 audio if available, TODO - or falling back to flash if not.
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author Ben Wallis
 */
;(function($){
    if(!$.depage){
        $.depage = {};
    }

    // shiv {{{
    /**
     * Shiv Audio
     *
     * Adds audio element to the DOM to enable recognition in IE < 9.
     *
     * @return void
     */
    if ($.browser.msie && $.browser.version < 9) {
        $('head').append('<style>audio{display:inline-block;*display:inline;*zoom:1}</style>');
        document.createElement("audio");
        document.createElement("source");
    }
    // }}}


    /**
     * Depage Audio
     *
     * @param el
     * @param index - player index
     * @param options
     *
     * @return context
     */
    $.depage.audio = function(el, index, options){
        // {{{ variables
        // To avoid scope issues, use 'base' instead of 'this' to reference this
        // class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.audio", base);

        // cache selectors
        var audio = $('audio', base.$el)[0];

        // set the player mode - 'html5' / 'flash' / false (fallback)
        var mode = false;

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
            base.options = $.extend({}, $.depage.audio.defaultOptions, options);

            $.depage.audio.instances[base.options.playerId] = base;
        };
        // }}}

        // {{{ audioSupport()
        /**
         * Audio Support
         *
         * Checks browser audio codec support.
         *
         * http://www.modernizr.com/
         *
         * @returns object
         *
         */
        base.audioSupport = function (){
            var support = {};

            try {
                support.wav = audio.canPlayType('audio/wav;').replace(/^no$/,'');
                support.ogg = audio.canPlayType('audio/ogg;').replace(/^no$/,'');
                support.mpeg = audio.canPlayType('audio/mpeg;').replace(/^no$/,'');
                support.mp3 = audio.canPlayType('audio/mp3;').replace(/^no$/,'');
            } catch(e) { }
            finally{
                // TODO flash support
                support.flash = false; // TODO $.depage.flash({requiredVersion:"9,0,115"}).detect();
            }

            return support;
        };
        // }}}

        // {{{ audio()
        /**
         * Audio
         *
         * Entry point to build the audio player.
         *  - Build the controls
         *  - Autoloads
         *
         * @return void
         */
        base.audio = function() {
            var support = base.audioSupport();

            // SET TO DEBUG FLASH MODE
            // support = { 'flash' : true };

            // determine the supported player mode - flash or html5
            if ( support.mp3 && $('source:[type="audio/mp3"]', audio).length > 0
                || support.ogg && $('source:[type="audio/ogg"]', audio).length > 0
                || support.wav && $('source:[type="audio/wav"]', audio).length > 0
                || support.mpeg && $('source:[type="audio/mpeg"]', audio).length > 0) {
                mode = 'html5';
                base.player = audio;
                base.html5.setup();
            } else if (support.flash) {
                 mode = 'flash';

                 // TODO FLASH PLAYER
            } else {
                // fallback
                return false;
            }

            var div = $("<div class=\"controls\"></div>");
            if (useCustomControls) {
                base.addControls(div);
            } else {
                $audio.attr("controls", "true");
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
             * HTML5 Setup the handlers for the HTML5 audio
             *
             * @return void
             */
            setup : function() {
                // attribute fixes issue with IE9 poster not displaying - add in html
                // $audio.attr('preload', 'none');

                $audio.bind("play", function(){
                    base.play();
                });

                $audio.bind("pause", function(){
                    base.pause();
                });

                $audio.bind("timeupdate", function(){
                    base.setCurrentTime(this.currentTime);
                });

                $audio.bind("ended", function(){
                    base.end();
                });

                /**
                 * HTML5 Progress Event
                 *
                 * Fired when buffering
                 *
                 * @return false
                 */
                $audio.bind("progress", function(){
                    var defer = null;
                    var progress = function(){
                        var loaded = 0;
                        if (audio.buffered && audio.buffered.length > 0 && audio.buffered.end && audio.duration) {
                            loaded = audio.buffered.end(audio.buffered.length-1) / audio.duration;
                        }
                        // for browsers not supporting buffered.end (e.g., FF3.6 and Safari 5)
                        else if (typeof(audio.bytesTotal) !== 'undefined' && audio.bytesTotal > 0
                                && typeof(audio.bufferedBytes) !== 'undefined') {
                            loaded = audio.bufferedBytes / audio.bytesTotal;
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
                $audio.bind("loadeddata", function(){
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
                $audio.bind("waiting", function(){
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
                $audio.bind("playing", function(){
                    base.html5.$buffer_image.hide();
                });


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
                /* TODO implement flash player

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
                 *
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
                */
            },
            // }}}

            // {{{ flash.insertPlayer()
            /**
             * Insert Flash Player
             *
             * Insert the flash object for the player using the depage flash plugin.
             *
             * Overwrites the audio container
             *
             * @return void
             */
            insertPlayer : function() {
                /* TODO FLASH PLAYER

                // get absolute url from source attribute with mp3-type
                var $link = $("<a href=\"" + $('source:[type="audio/mp3"]', audio).attr("src") + "\"></a>").appendTo("body");
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
                    id     : base.options.playerId,
                    wmode  : 'transparent',
                    params : flashParams
                });

                // use innerHTML for IE < 9 otherwise player breaks!!
                $wrapper[0].innerHTML = html.plainhtml;

                base.player.initialized = true;

                base.player.load(url);
                */
            }
            // }}}
        };

        // {{{ addLegend()
        /**
         * Add Legend-Wrapper
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

            $audio.removeAttr("controls");

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
            if (useCustomControls){
                base.controls.play.hide();
                base.controls.pause.show();
                base.controls.rewind.show();
            }

            base.options.onPlay && base.options.onPlay();
        };
        // }}}

        // {{{ pause()
        /**
         * Pause
         *
         * @return void
         */
        base.pause = function() {
            if (useCustomControls){
                base.controls.play.show();
                base.controls.pause.hide();
                base.controls.rewind.show();
            }

            base.options.onPause && base.options.onPause();
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

        // Build the audio
        base.audio();

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
     * @param playerId
     * @param width - audio width
     * @param debug - if set, the flash player will send console.log messages for his actions
     * @param onPlay - pass callback function to trigger on play event
     * @param onPause - pass callback function to trigger on pause event
     * @param onEnd - pass callback function to trigger on end play event
     */
    $.depage.audio.defaultOptions = {
        use_custom_controls: false,
        assetPath : basePath + "depage_audio/",
        playerId : "dpAudio",
        debug: false,
        onPlay: false,
        onPause: false,
        onEnd: false
    };

    $.fn.depageAudio = function(options){
        return this.each(function(index){
            (new $.depage.audio(this, index, options));
        });
    };

})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
