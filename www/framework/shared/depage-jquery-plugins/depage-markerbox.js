/**
 * @require framework/shared/jquery-1.12.3.min.js
 *
 * @file    depage-marker-box
 *
 * Object for extending in plugins that need markerbox functionality
 *
 * copyright (c) 2006-2012 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Ben Wallis
 */
(function($){
    if(!$.depage){
        $.depage = {};
    }

    /**
     * markerbox
     *
     * @param options
     */
    $.depage.markerbox = function(options) {
        var base = {};

        String.prototype.replaceAt=function(index, replacement) {
            return this.substr(0, index) + replacement+ this.substr(index + replacement.length);
        }

        // {{{ init
        /**
         * Init
         *
         * Get the plugin options.
         *
         * @return void
         */
        base.init = function(){
            base.options = $.extend({}, $.depage.markerbox.defaultOptions, options);
        };
        // }}}

        $.extend(base, {
            // reference wrapper divs
            $dialogue : null,
            $wrapper : null,
            $contentWrapper : null,
            $directionMarker : null,

            // {{{ show()
            /**
             * Show
             *
             * @return void
             */
            show : function(left, top) {
                left = left || this.$el.offset().left + this.$el.width();
                top = top || Math.ceil(this.$el.offset().top - this.$el.height());

                base.addWrapper();
                base.setContent(base.options.title, base.options.message, base.options.icon);
                base.setPosition(top, left, base.options.direction);

                // bind escape key to cancel
                $(document).bind('keyup.marker', function(e){
                    var key = e.which || e.keyCode;
                    if (key == 27) {
                        base.hide();
                    }
                });

                // stop propagation of hide when clicking inside the wrapper or input
                $wrapper.click(function(e) {
                    e.stopPropagation();
                });

                if (base.options.bind_el) {
                    this.$el.click(function(e) {
                        e.stopPropagation();
                    });
                }

                // hide dialog when clicked outside
                $(document).bind("click.marker", function() {
                    base.hide();
                });

                // allow chaining
                return this;
            },
            // }}}

            // {{{ hide()
            /**
             * Hide
             *
             * @param duration - gradually fades out default 300
             * @param callback - optional callback function
             *
             * @return void
             */
            hide : function(duration, callback) {
                $(document).unbind("click.marker").unbind('keyup.marker');
                if (!$dialogue) return;

                duration = duration || base.options.fadeoutDuration;
                $wrapper.fadeOut(duration, callback);

                // @todo restore previous focused element?

                // allow chaining
                return this;
            },
            // }}}

            // {{{ hideAfter()
            /**
             * HideAfter
             *
             * hides dialog automatically after a duration
             *
             * @param duration - duration after
             * @param callback - optional callback function
             *
             * @return void
             */
            hideAfter : function(duration, callback) {
                setTimeout(function(){
                    base.hide(base.options.fadeoutDuration, callback);
                }, duration);

                // allow chaining
                return this;
            },
            // }}}

            // {{{ addWrapper()
            /**
             * removes old and adds the new html wrapper
             *
             * @return void
             */
            addWrapper : function() {
                // remove old wrapper (also with multiple dialogues)
                $('#' + base.options.id).remove();

                $dialogue = $('<div />');

                $wrapper = $('<div class="wrapper" />');
                $dialogue.append($wrapper);

                if (base.options.directionMarker) {
                    // add direction marker
                    $directionMarker = $('<span class="direction-marker" />');
                    $wrapper.append($directionMarker);
                }

                $contentWrapper = $('<div class="message" />');
                $wrapper.append($contentWrapper);

                $("body").append($dialogue);

                $wrapper.data("depage.markerbox", base);
                $dialogue.attr({
                    'class': "depage-markerbox " + base.options.classes,
                    'id': base.options.id
                });

                // allow chaining
                return this;
            },
            // }}}

            // {{{ setPosition()
            /**
             * set the position of the dialogue including the direction marker
             *
             * @return void
             */
            setPosition : function(newTop, newLeft, direction) {
                $dialogue.attr("style", "position: absolute; top: " + newTop + "px; left: " + newLeft + "px; z-index: 10000");

                direction = direction.toLowerCase();
                directions = {
                    l: 'left',
                    r: 'right',
                    t: 'top',
                    b: 'bottom',
                    c: 'center'
                };

                var wrapperHeight = $wrapper.height();
                var wrapperWidth = $wrapper.width();
                var paddingLeft = parseInt($wrapper.css("padding-left"), 10);
                var paddingRight = parseInt($wrapper.css("padding-right"), 10);
                var paddingTop = parseInt($wrapper.css("padding-top"), 10);
                var paddingBottom = parseInt($wrapper.css("padding-bottom"), 10);
                var dHeight = 0,
                    dWidth = 0;

                if (typeof($directionMarker) !== "undefined") {
                    dHeight = $directionMarker.height();
                    dWidth = $directionMarker.width();
                } else {
                    dHeight = - paddingTop * 2;
                    dWidth = - paddingLeft * 2;
                }

                // adjust position to always be inside of view
                // @todo center on very small screens?
                if (newLeft + wrapperWidth + dWidth > $(window).width() - 20) {
                    console.log("move from left to right");
                    if (direction[0] == "l") {
                        direction = direction.replaceAt(0, "r");
                    }
                    if (direction[1] == "l") {
                        direction = direction.replaceAt(1, "r");
                    }
                } else if (newLeft - wrapperWidth - dWidth < 20) {
                    console.log("move from right to left");
                    if (direction[0] == "r") {
                        direction = direction.replaceAt(0, "l");
                    }
                    if (direction[1] == "r") {
                        direction = direction.replaceAt(1, "l");
                    }
                }

                var wrapperPos = {};
                var markerPos = {};

                // to which side will the direction-marker attached to
                switch (direction[0]) {
                    case 't': // top
                        wrapperPos.top = dHeight / 2;
                        markerPos.top = -dHeight;
                        break;
                    case 'b': // bottom
                        wrapperPos.bottom = dHeight / 2;
                        markerPos.bottom = -dHeight;
                        break;
                    case 'l': // left
                        wrapperPos.left = dWidth / 2;
                        markerPos.left = -dWidth;
                        break;
                    case 'r': // right
                        wrapperPos.right = dWidth / 2;
                        markerPos.right = -dWidth;
                        break;
                    case 'c': // center
                        wrapperPos.left = - (wrapperWidth + paddingLeft + paddingRight) / 2;
                        wrapperPos.top = - (wrapperHeight + paddingTop + paddingBottom) / 2;
                        break;
                }

                // on which position will it be displayed
                switch (direction[1]) {
                    case 'l': // left
                        wrapperPos.left = -paddingLeft - dWidth / 2;
                        markerPos.left = paddingLeft;
                        break;
                    case 'r': // right
                        wrapperPos.right = -paddingRight - dWidth / 2;
                        markerPos.right = paddingRight;
                        break;
                    case 'c': // center
                        if (direction[0] == "t" || direction[0] == "b") { // horizontal
                            wrapperPos.left = - (wrapperWidth + paddingLeft + paddingRight) / 2;
                            markerPos.left = (wrapperWidth + paddingLeft + paddingRight) / 2 - dWidth / 2;
                        } else if (direction[0] == "l" || direction[0] == "r") { // vertical
                            wrapperPos.top = - (wrapperHeight + paddingTop + paddingBottom) / 2;
                            markerPos.top = (wrapperHeight + paddingTop + paddingBottom) / 2 - dHeight / 2;
                        }
                        break;
                    case 't': // top
                        if (wrapperPos.top) {
                            wrapperPos.top = -paddingTop - dHeight / 2;
                        }
                        markerPos.top = paddingTop;
                        break;
                    case 'b': // bottom
                        if (wrapperPos.bottom) {
                            wrapperPos.bottom = -paddingBottom - dHeight / 2;
                        }
                        markerPos.bottom = paddingBottom;
                        break;
                }

                $wrapper.css(wrapperPos);
                if (typeof($directionMarker) !== "undefined") {
                    $directionMarker.css(markerPos).attr("class", "direction-marker " + directions[direction[0]]);
                }
            },
            // }}}

            // {{{ setContent()
            /**
             * setContent
             *
             * @param title
             * @param message
             * @param icon (optional)
             *
             * @return void
             */
            setContent : function(title, message, icon) {
                var $title = $('<h1 />').text(title);
                var $message = $('<p />').text(message);

                $contentWrapper.empty()
                    .append($title)
                    .append($message);

                // allow chaining
                return this;
            }
        });

        base.init();

        return base;
    };

    /**
     * Default Options
     *
     * id - the id of the dialogue element wrapper to display
     * message - message the dialouge will display
     * buttons - buttons to supply (with corresponding event triggered)
     * classes - css classes to supply to the wrapper and content elements
     *
     */
    $.depage.markerbox.defaultOptions = {
        id : 'depage-markerbox',
        classes : 'depage-markerbox',
        icon: '',
        title: '',
        message: '',
        direction : 'TL',
        directionMarker : null,
        fadeoutDuration: 300
    };
    // }}}
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
