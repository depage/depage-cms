$(document).ready(function() {
    var initComments = function () {
        $(".depage-comments[data-comments-url]").each( function() {
            var $comments = $(this);
            var commentUrl = $(this).attr('data-comments-url') + "&ajax=true";

            // {{{ setupCommentForm
            var setupCommentForm = function(form) {
                var $form = $(form);
                var $senderData = $form.find(".sender-data");

                if ($form.find("input[name='name']").val() === "") {
                    $senderData.hide();
                }

                $form.find("textarea").focus( function() {
                    $senderData.slideDown();
                } );

                setupForm($form);
                $form.submit( function() {
                    form.data = form.data || {};

                    var data = {};
                    
                    $("input, select, textarea", $form).each( function () {
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
                    
                    $(this).before("<div class=\"loading\"><span>sending comment</span></div>");
                    $(this).hide();

                    $.ajax({
                        url: commentUrl,
                        data: data,
                        type: 'POST',
                        xhrFields: {
                            withCredentials: true
                        },
                        success: function(data, textStatus, xhr) {
                            $comments.html( $("<div>").append(data).find(".depage-comments > *") );
                            setupCommentForm($comments.find("form"));
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert("Comment could not be sent - please try again later");
                            $comments.find(".loading").remove();
                            $form.show();
                        },
                        complete: function(jqXHR, textStatus) {
                        }
                    });

                    return false;
                });
            };
            // }}}
            // {{{ loadComments()
            var loadComments = function () {
                $comments.append("<div class=\"loading\"><span>loading comments</span></div>");
                $.ajax({
                    url: commentUrl,
                    type: 'GET',
                    xhrFields: {
                        withCredentials: true
                    },
                    success: function(data, textStatus, xhr) {
                        $comments.html( $("<div>").append(data).find(".depage-comments > *") );
                        setupCommentForm($comments.find("form"));
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $comments.html( $("<div>error while loading comments: " + textStatus + "</div>") );
                    },
                    complete: function(jqXHR, textStatus) {
                    }
                });
            };
            // }}}
            
            loadComments();
        });
    };

    initComments();
    $(window).bind("statechangecomplete", initComments);
});
/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
