/**
 * @require framework/shared/jquery-1.4.2.js
 * @require framework/shared/jquery.hammer.js
 *
 * @file    depage-magaziner.js
 *
 * adds a magazine like navigation to a website
 *
 *
 * copyright (c) 2013 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 **/
;(function($){
    "use strict";
    /*jslint browser: true*/
    /*global $:false History:false reinvigorate:false */

    if(!$.depage){
        $.depage = {};
    }

    var rootUrl = History.getRootUrl();

    // {{{ jquery.internal expression helper
    $.expr[':'].internal = function(obj, index, meta, stack){
        var url = $(obj).attr('href') || $(obj).attr('src') || '';

        // Check link
        return url.substring(0, rootUrl.length) === rootUrl || url.indexOf(':') === -1;
    };
    // }}}
    // {{{ HTML Helper
    var documentHtml = function(html){
        // Prepare
        var result = String(html)
            .replace(/<\!DOCTYPE[^>]*>(\n)?/i, '')
            .replace(/<(body)[\s]class="([^"]*)"([\s\>])/gi,'<div class="document-$1 $2"$3')
            .replace(/<(html|head|body|title|meta|script)([\s\>])/gi,'<div class="document-$1"$2')
            .replace(/<\/(html|head|body|title|meta|script)\>/gi,'</div>')
        ;

        return result;
    };
    // }}}
    // {{{ jquery.ajaxify Helper
    $.fn.ajaxify = function() {
        var $this = $(this);

        // Ajaxify
        $this.find('a:internal:not(.no-ajaxy)').each(function(){
            // Prepare
            var
                $this = $(this),
                url = this.href,
                title = $this.attr('title') || null;

            // make links absolute;
            $this.attr("href", url);

            $this.click(function(e) {
                // Continue as normal for cmd clicks etc
                if ( e.which == 2 || e.metaKey ) { return true; }

                // Ajaxify this link
                History.pushState(null,title,url);
                e.preventDefault();
                return false;
            });
        });

        // Chain
        return $this;
    };
    // }}}
    // {{{ makeAbsolute
    function makeAbsolute(base, relative) {
        var stack = base.split("/"),
            parts = relative.split("/");
        stack.pop(); // remove current file name (or empty string)
                    // (omit if "base" is the current folder without trailing slash)
        for (var i=0; i<parts.length; i++) {
            if (parts[i] == ".")
                continue;
            if (parts[i] == "..")
                stack.pop();
            else
                stack.push(parts[i]);
        }
        return stack.join("/");
    }
    // }}}

    $.depage.magaziner = function(el, pagelinkSelector, options){
        if (!History.enabled) {
            return;
        }

        // {{{ variables
        // To avoid scope issues, use 'base' instead of 'this' to reference this class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object
        base.$el.data("depage.magaziner", base);

        // jquery object of body
        var $body = $("body");
        var $window = $(window);
        var $document = $(document);

        // holds page-numbers by urls
        var pagesByUrl = [];
        var urlsByPages = [];

        //list of currently loaded pages
        var $pages = base.$el.children(".page");

        // width of one page
        var pageWidth = base.$el.width();

        // speed for animations
        var speed = 300;

        // global hammer options to drag only in one direction
        var hammerOptions = {
            drag_lock_to_axis: true
        };
        var scrollTop;

        // get the currently loaded page
        base.currentPage = -1;
        var $currentPage = null;
        // }}}

        // {{{ init()
        base.init = function() {
            base.options = $.extend({},$.depage.magaziner.defaultOptions, options);

            var $pagelinks = $(pagelinkSelector);
            for (var i = 0; i < $pagelinks.length; i++) {
                var url = $pagelinks[i].href;
                if ($pagelinks.eq(i).attr("href") === "") {
                    // normalize empty links that go to current page for IE
                    url = document.location.href;
                }
                pagesByUrl[url] = i;
                urlsByPages[i] = url;
            }

            base.registerEvents();
            $body.ajaxify();

            var currentPage = pagesByUrl[document.location.href];
            $currentPage = $(".page").addClass("current-page");
            var beforeHtml = "";
            var afterHtml = "";

            $currentPage.data("loaded", true);
            $currentPage.data("title", document.title);

            // add empty page containers
            for (i = 0; i < $pagelinks.length; i++) {
                if (i < currentPage) {
                    beforeHtml += "<div class=\"page\" style=\"display: none\"></div>";
                } else if (i > currentPage) {
                    afterHtml += "<div class=\"page\" style=\"display: none\"></div>";
                }
            }
            $(beforeHtml).insertBefore($currentPage);
            $(afterHtml).insertAfter($currentPage);

            $pages = base.$el.children(".page");
            $pages.not(".current-page").data("loaded", false);


            base.$el.triggerHandler("depage.magaziner.initialized");

            base.preloadPage(currentPage - 1);
            base.preloadPage(currentPage + 1);

            base.show(currentPage);
        };
        // }}}
        // {{{ registerEvents()
        base.registerEvents = function() {
            // {{{ prevent default behaviour on touchmove to disable native scrolling
            base.$el.on('touchmove', function (e) {
                e.preventDefault();
            });
            // }}}

            // {{{ horizontal scrolling between pages
            base.$el.hammer(hammerOptions).on("dragleft", function(e) {
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.css({
                        left: (i - base.currentPage) * pageWidth + e.gesture.deltaX
                    });
                });
            });
            base.$el.hammer(hammerOptions).on("dragright", function(e) {
                $pages.each( function(i) {
                    var $page = $(this);
                    $page.css({
                        left: (i - base.currentPage) * pageWidth + e.gesture.deltaX
                    });
                });
            });
            // }}}
            // {{{ vertical scrolling
            base.$el.hammer(hammerOptions).on("dragup", function(e) {
                base.$el.css({
                    top: e.gesture.deltaY
                });
            });
            base.$el.hammer(hammerOptions).on("dragdown", function(e) {
                base.$el.css({
                    top: e.gesture.deltaY
                });
            });
            // }}}
            // {{{ dragend actions after horizontal or vertical scrolling
            base.$el.hammer(hammerOptions).on("dragend", function(e) {
                var newXOffset = 0;
                var newYOffset = 0;

                if (e.gesture.deltaX < - pageWidth / 3 || (e.gesture.deltaX < 0 && e.gesture.velocityX > 1)) {
                    base.next();
                } else if (e.gesture.deltaX > pageWidth / 3 || (e.gesture.deltaX > 0 && e.gesture.velocityX > 1)) {
                    base.prev();
                } else {
                    base.show(base.currentPage);
                }
                if (e.gesture.deltaY < 0 && e.gesture.velocityY > 0.2) {
                    newYOffset = -1;
                } else if (e.gesture.deltaY > 0 && e.gesture.velocityY > 0.2) {
                    newYOffset = 1;
                }

                // vertical scrolling on current page
                base.$el.css({
                    top: 0
                });
                var currentPos = $window.scrollTop() - e.gesture.deltaY;
                var targetPos = $window.scrollTop() - e.gesture.deltaY - 300 * e.gesture.velocityY * newYOffset;

                window.scrollTo(0, $window.scrollTop() - e.gesture.deltaY);

                $pages.not(".current-page").css({
                    top: currentPos
                });

                if (newYOffset !== 0) {
                    $("html, body").animate({
                        scrollTop: targetPos
                    }, 300 * e.gesture.velocityY);
                }
            });
            // }}}
            // {{{ key events
            $document.on("keyup", function(e) {
                if ($(document.activeElement).is(':input')){
                    // continue only if an input is not the focus
                    return true;
                }
                if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) {
                    return true;
                }
                switch (parseInt(e.which || e.keyCode, 10)) {
                    case 39 : // cursor right
                    case 76 : // vim nav: l
                        base.next();
                        e.preventDefault();
                        break;
                    case 37 : // cursor left
                    case 72 : // vim nav: h
                        base.prev();
                        e.preventDefault();
                        break;
                    case 74 : // vim nav: j
                        window.scrollTo(0, $window.scrollTop() + 50);
                        e.preventDefault();
                        break;
                    case 75 : // vim nav: k
                        window.scrollTo(0, $window.scrollTop() - 50);
                        e.preventDefault();
                        break;
                }
            });
            // }}}

            // {{{ scroll event
            $window.scroll( function() {
                $pages.not(".current-page").css({
                    top: $window.scrollTop()
                });
            });
            // }}}
            // {{{ resize event
            var onResize =  function() {
                pageWidth = base.$el.width();
                base.show(base.currentPage);
            };

            $window.on("resize", function() {
                setTimeout( onResize, 200 );
            });
            // }}}

            // {{{ statechange event
            $window.bind("statechange", function() {
                var
                    State = History.getState(),
                    url = State.url,
                    relativeUrl = url.replace(rootUrl,'');

                if (typeof pagesByUrl[url] !== undefined) {
                    base.show(pagesByUrl[url]);
                }
            });
            // }}}
            // {{{ statechangecomplete event
            $window.bind("statechangecomplete", function() {
                var
                    State = History.getState(),
                    url = State.url,
                    title = $currentPage.data("title");

                if (title) {
                    // Update the title
                    document.title = title;
                    try {
                        document.getElementsByTagName('title')[0].innerHTML = document.title.replace('<','&lt;').replace('>','&gt;').replace(' & ',' &amp; ');
                    }
                    catch ( Exception ) { }
                }

                // Inform Google Analytics of the change
                if ( typeof window._gaq !== 'undefined' ) {
                    window._gaq.push(['_trackPageview', url]);
                }

                // Inform ReInvigorate of a state change
                if ( typeof window.reinvigorate !== 'undefined' && typeof window.reinvigorate.ajax_track !== 'undefined' ) {
                    reinvigorate.ajax_track(url);
                    // ^ we use the full url here as that is what reinvigorate supports
                }

                // Inform piwik of the change
                if ( typeof window._paq !== 'undefined' ) {
                    window._paq.push(['trackPageView', url]);
                }
            });
            // }}}
        };
        // }}}

        // {{{ showPagesAround(n)
        base.showPagesAround = function(n) {
            $pages.eq(n - 1).show();
            $pages.eq(n).show();
            $pages.eq(n + 1).show();
        };
        // }}}
        // {{{ preloadPage()
        base.preloadPage = function(n) {
            if (n < 0 || n >= $pages.length) {
                return;
            }
            var url = urlsByPages[n];

            if (typeof url === 'undefined') {
                return;
            }

            // Prepare Variables
            var relativeUrl = url.replace(rootUrl,'');

            // get page element for current url
            var $page = $pages.eq(n);

            if ($page.data("loaded")) {
                // data is already loaded into element
                if (url ===  document.location.href) {
                    $window.trigger("statechangecomplete");
                }

                return true;
            }

            $page.addClass("loading");

            // Ajax Request the Traditional Page
            $.ajax({
                url: url,
                success: function(data, textStatus, jqXHR){
                    // Prepare
                    var
                        $data = $(documentHtml(data)),
                        $dataBody = $data.find('.document-body:first'),
                        $dataContent = $dataBody.find(".page").filter(':first'),
                        contentHtml,
                        $scripts;

                    // Fetch the scripts
                    $scripts = $dataContent.find('.document-script');
                    if ( $scripts.length ) {
                        $scripts.detach();
                    }

                    $data.find("a:internal").each( function() {
                        var $el = $(this);
                        $el.attr("href", makeAbsolute(url, $el.attr("href")));
                    });
                    $data.find("img:internal, iframe:internal").each( function() {
                        var $el = $(this);
                        $el.attr("src", makeAbsolute(url, $el.attr("src")));
                    });

                    // Fetch the content
                    contentHtml = $dataContent.html() || $data.html();
                    if ( !contentHtml ) {
                        document.location.href = url;
                        return false;
                    }

                    // Update the content
                    $page.html(contentHtml).ajaxify();

                    // Add the scripts
                    $scripts.each(function(){
                        var $script = $(this), scriptText = $script.text(), scriptNode = document.createElement('script');
                        scriptNode.appendChild(document.createTextNode(scriptText));
                        //contentNode.appendChild(scriptNode);
                    });

                    $body.attr('class', $dataBody.attr("class"));
                    $body.removeClass('document-body');

                    $page.removeClass('loading');
                    $page.data("loaded", true);
                    $page.data("title", $data.find('.document-title:first').text());

                    if (url === document.location.href) {
                        $window.trigger("statechangecomplete");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    document.location.href = url;
                    return false;
                }
            }); // end ajax
        };
        // }}}
        // {{{ clearPage()
        base.clearPage = function(n) {
            var $page = $pages.eq(n);

            $page.data("loaded", false);
            $page.empty();
        };
        // }}}
        // {{{ show()
        base.show = function(n) {
            var resetScroll = base.currentPage != n;

            base.currentPage = n;
            base.showPagesAround(base.currentPage);
            base.preloadPage(n);

            // horizontal scrolling between pages
            $pages.each( function(i) {
                var $page = $(this);
                $page.stop().animate({
                    left: (i - base.currentPage) * pageWidth
                }, speed);
            });
            if (resetScroll) {
                $pages.last().queue( function() {
                    window.scrollTo(0, 0);

                    $pages.css({
                        top: 0
                    });
                    $pages.hide();
                    base.showPagesAround(base.currentPage);

                    base.preloadPage(base.currentPage - 1);
                    base.preloadPage(base.currentPage + 1);
                });

                $pages.removeClass("current-page");
                $currentPage = $pages.eq(n);
                $currentPage.addClass("current-page");

                base.$el.triggerHandler("depage.magaziner.show", [n]);
            }

            if (resetScroll && document.location.href != urlsByPages[base.currentPage]) {
                History.pushState(null, null, urlsByPages[base.currentPage]);
            }
        };
        // }}}
        // {{{ next()
        base.next = function() {
            if (base.currentPage < $pages.length - 1) {
                // scroll to next page
                base.show(base.currentPage + 1);
                base.$el.triggerHandler("depage.magaziner.next");

            } else {
                base.show(base.currentPage);
            }
        };
        // }}}
        // {{{ prev()
        base.prev = function() {
            if (base.currentPage > 0) {
                // scroll to previous page
                base.show(base.currentPage - 1);
                base.$el.triggerHandler("depage.magaziner.prev");
            } else {
                base.show(base.currentPage);
            }
        };
        // }}}

        // Run initializer
        setTimeout(base.init, 50);
    };

    $.depage.magaziner.defaultOptions = {
        option1: "default"
    };

    $.fn.depageMagaziner = function(pagelinkSelector, options){
        return this.each(function(){
            (new $.depage.magaziner(this, pagelinkSelector, options));
        });
    };

})(jQuery);
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
