$(document).ready(function() {
    $(".depage-comments[data-comments-url]").each( function() {
        var $comments = $(this);
        var commentUrl = $(this).attr('data-comments-url') + "?ajax=true";

        var setupCommentForm = function(form) {
            var $form = $(form);

            setupForm($form);
            $form.submit( function() {
                form.data = form.data || {};

                var data = {};
                
                $("input, select, textarea", form).each( function () {
                    var type = $(this).attr("type");
                    if ((type == "radio")) {
                        if (this.checked) {
                            data[this.name] = this.value;
                        }
                    } else if (type == "checkbox") {
                        if (this.checked) {
                            data[this.name] = data[this.name] || [];
                            data[this.name].push(this.value);
                        }
                    } else {
                        data[this.name] =  $(this).val();
                    }
                });
                
                $comments.load(commentUrl + ' .depage-comments > *', data, function() {
                    setupCommentForm($comments.find("form"));
                });

                return false;
            });
        }

        $comments.load(commentUrl + ' .depage-comments > *', function() {
            setupCommentForm($comments.find("form"));
        });
    });
});
