$(document).ready(function() {
    $("section.example").each(function() {
        var source;

        source = $(this).html();
        source = "<script src=\"jquery-1.9.1.min.js\"></script>\n<script src=\"depage-slideshow.js\"></script>" + source;
        source = source.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/^[ ]{12}/gm, "");
        source = "<pre class=\"prettyprint lang-html\"><code class=\"html\">" + source + "</code></pre>";

        $(this).append(source);
    });

    prettyPrint();
});

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
