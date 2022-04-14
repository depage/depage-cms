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

        String.prototype.replaceAt = function(index, replacement) {
            return this.substr(0, index) + replacement+ this.substr(index + replacement.length);
        };

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
            show: function(left, top) {
                var direction = base.options.direction.toLowerCase();
                var that = this;

                that.addWrapper();
                that.setContent(base.options.title, base.options.message, base.options.icon);

                setTimeout(function() {
                    // defer setting position until element has added all children
                    that.setPosition(top, left, base.options.direction);
                    that.$wrapper.addClass("visible");
                }, 10);

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
            hide: function(duration, callback) {
                duration = duration || base.options.fadeoutDuration;

                if (!this.$wrapper) return this;

                this.$wrapper.removeClass("visible");

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

                this.$dialogue = $('<div />');

                this.$wrapper = $('<div class="wrapper" />');
                this.$dialogue.append(this.$wrapper);

                if (base.options.directionMarker) {
                    // add direction marker
                    this.$directionMarker = $('<span class="direction-marker" />');
                    this.$wrapper.append(this.$directionMarker);
                }

                this.$contentWrapper = $('<div class="message" />');
                this.$wrapper.append(this.$contentWrapper);

                $("body").append(this.$dialogue);

                this.$wrapper.data("depage.markerbox", base);
                this.$dialogue.attr({
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
            setPosition : function(top, left, d) {
                d = d.toLowerCase();
                directions = {
                    tl: 'right',
                    cl: 'right',
                    bl: 'right',
                    tr: 'left',
                    br: 'left',
                    cr: 'left',
                    tc: 'bottom',
                    cc: '',
                    bc: 'top',
                };

                var wrapperHeight = this.$wrapper.height();
                var wrapperWidth = this.$wrapper.width();
                var paddingLeft = parseInt(this.$wrapper.css("padding-left"), 10);
                var paddingRight = parseInt(this.$wrapper.css("padding-right"), 10);
                var paddingTop = parseInt(this.$wrapper.css("padding-top"), 10);
                var paddingBottom = parseInt(this.$wrapper.css("padding-bottom"), 10);
                var offset = base.options.positionOffset;
                var dHeight = 0,
                    dWidth = 0,
                    pos;

                if (typeof(this.$directionMarker) !== "undefined") {
                    dHeight = this.$directionMarker.height();
                    dWidth = this.$directionMarker.width();
                } else {
                    dHeight = - paddingTop * 2;
                    dWidth = - paddingLeft * 2;
                }

                pos = this.adjustPositionToElement(top, left, d, this.$el);

                // adjust position to always be inside of view
                // @todo center on very small screens?
                var elWidth = !left ? this.$el.width() : 0;
                var outOfBoundsLeft = pos.left - wrapperWidth - dWidth - elWidth < 10;
                var outOfBoundsRight = pos.left + wrapperWidth + dWidth + elWidth > $(window).width() - 10;

                if (outOfBoundsLeft && outOfBoundsRight) {
                    d = d.replaceAt(1, "c");
                } else if (d[1] == "r" && outOfBoundsRight) {
                    d = d.replaceAt(1, "l");
                } else if (d[1] == "l" && outOfBoundsLeft) {
                    d = d.replaceAt(1, "r");
                }
                pos = this.adjustPositionToElement(top, left, d, this.$el);

                this.$dialogue.attr("style", "position: absolute; top: " + pos.top + "px; left: " + pos.left + "px; z-index: 10000");

                var wrapperPos = {};
                var markerPos = {};

                // vertical
                switch (d[0]) {
                    case 't': // top
                        wrapperPos.top = - paddingTop - dHeight * 0.5;
                        if (d[1] != "c") markerPos.top = paddingTop;
                        break;
                    case 'c': // center
                        wrapperPos.top = - (wrapperHeight + paddingTop + paddingBottom) / 2;
                        break;
                    case 'b': // bottom
                        wrapperPos.bottom = - paddingBottom - dHeight * 0.5;
                        if (d[1] != "c") markerPos.bottom = paddingBottom;
                        break;
                }

                // horizontal
                switch (d[1]) {
                    case 'l': // left
                        wrapperPos.right = dHeight + offset - dHeight * 0.5;
                        markerPos.right = -dHeight;
                        break;
                    case 'r': // right
                        wrapperPos.left = dHeight + offset - dHeight * 0.5;
                        markerPos.left = -dHeight;
                        break;
                    case 'c': // center
                        wrapperPos.left = - (wrapperWidth + paddingLeft + paddingRight) / 2;
                        if (d[0] == "t") {
                            wrapperPos.top -= (wrapperHeight + paddingTop + offset);
                        } else if (d[0] == "b") {
                            wrapperPos.bottom -= (wrapperHeight + paddingBottom + offset);
                        }
                        if (d[0] == "t" || d[0] == "b") { // horizontal
                            markerPos.left = (wrapperWidth + paddingLeft + paddingRight) / 2 - dWidth / 2;
                        }
                        break;
                }
                if (d == "tc") {
                    markerPos.bottom = -dHeight;
                } else if (d == "bc") {
                    markerPos.top = -dHeight;
                }

                this.$wrapper.css(wrapperPos);
                if (typeof(this.$directionMarker) !== "undefined") {
                    this.$directionMarker.css(markerPos).attr("class", "direction-marker " + directions[d]);
                }
            },
            // }}}
            // {{{ adjustPositionToElement()
            /**
             * set the position of the dialogue including the direction marker
             *
             * @return void
             */
            adjustPositionToElement : function(top, left, d, $el) {
                d = d.toLowerCase();
                var o = $el.offset();

                // get position from current element
                if (!left) {
                    left = o.left;

                    if (d[1] == "r") {
                        left += $el.width();
                    } else if (d[1] == "c") {
                        left += $el.width() * 0.5;
                    }
                }
                if (!top) {
                    top = o.top;

                    if (d[0] == "t" && d[1] != "c") {
                        top += base.options.positionOffset;
                    } else if (d[0] == "b" && d[1] != "c") {
                        top += $el.height() - base.options.positionOffset;
                    } else if (d[0] == "b") {
                        top += $el.height();
                    } else if (d[0] == "c") {
                        top += $el.height() * 0.5;
                    }
                }
                top = Math.ceil(top);
                left = Math.ceil(left);

                return {
                    top: Math.ceil(top),
                    left: Math.ceil(left)
                };
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
                if (typeof title == 'function') {
                    title = title();
                }
                if (typeof message == 'function') {
                    message = message();
                }
                var $title = $('<h1 />').text(title);
                if (Object.getPrototypeOf(message).jquery) {
                    var $message = message.clone();
                } else {
                    var $message = $('<p />').text(message);
                }

                this.$contentWrapper.empty()
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
        positionOffset: 10,
        fadeinDuration: 300,
        fadeoutDuration: 300
    };
    // }}}
})(jQuery);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
