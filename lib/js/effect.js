/*
 * @require framework/shared/jquery-1.12.3.min.js
 * @require framework/HtmlForm/lib/js/jquery.tools.js
 * @require framework/HtmlForm/lib/js/depage-richtext.js
 */

// {{{ add validation-"effects"
$.tools.validator.addEffect('depageEffect', function(errors, event) {
    // "show" function
    $.each(errors, function(index, error) {
        // erroneous input paragraph
        var $input = $(error.input);
        var errorParagraph = $input.parents('p');

        // if there's no error message
        var $errorMessage = errorParagraph.find('.errorMessage');
        if ($errorMessage.length === 0) {
            // add error notices
            errorParagraph.append('<span class="errorMessage">' + errorParagraph.attr('data-errorMessage')+ '</span>');
            errorParagraph.addClass('error');
        } else {
            $errorMessage.show();
        }
    });

// remove error notices when inputs turn valid
}, function(inputs) {
    $.each(inputs, function(index, input) {
        var inputParagraph = $(input).parents('p');
        inputParagraph.removeClass('error');
        inputParagraph.find('.errorMessage').remove();
    });
});
// }}}

// {{{ setupForm()
function setupForm(form) {
    var $form = $(form);
    var check = $form.attr('data-jsvalidation');
    var autosave = $form.attr('data-jsautosave');

    // {{{ validate
    // validate on blur or change
    if ((check == 'blur') || (check == 'change')) {
        // @todo add also event for "submit" nonetheless
        $form.validator({
            effect: 'depageEffect',
            inputEvent: check,
            errorInputEvent: check
        });
    // validate on submit
    } else if (check == 'submit') {
        $form.validator({
            effect: 'depageEffect',
            // do not validate inputs when they are edited
            errorInputEvent: null
        });
    }
    $form.find("p.cancel input[type='submit'], p.back input[type='submit']").on("click", function() {
        // disable browser validation before submitting
        $form.attr('novalidate', 'novalidate');

        // disable custom js validator
        $form.validator({
            formEvent: null
        });

        return true;
    });
    // }}}
    // {{{ add inFail and onSuccess handlers
    $form.bind("onFail", function(e, errors) {
        // error found
        $form.find(".submit").addClass("error");
    });
    $form.bind("onSuccess", function(e, errors) {
        // count error messages, because onSuccess is called for a successful validated input
        var errNum = $form.find(".errorMessage").length;

        if (errNum > 0) {
            $form.find(".submit").addClass("error");
        } else {
            $form.find(".submit").removeClass("error");
        }
    });
    // }}}
    // {{{ hide errormessages on click
    $form.delegate('.error input', 'focus', function(event) {
        $(this).parents('.error').find('.errorMessage').hide();
    });
    // }}}
    // {{{ focus input on click on error message
    $form.delegate('.errorMessage', 'click', function(event) {
        var $input = $(this).parents('.error').find('input, select, textarea').focus();
        var e;

        try {
            // try to open select on click
            e = document.createEvent('MouseEvents');
            e.initMouseEvent("mousedown", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
            $input[0].dispatchEvent(e);
        } catch (exception) {
        }
    });
    // }}}


    // {{{ autosave
    if (autosave == "true") {
        var saveInterval = 1000;
        var now = new Date();

        form.data = form.data || {};
        form.data.saving = false;
        form.data.lastsave = now.getTime();
        form.data.timer = null;

        form.data.autosave = function() {
            var data = {};

            $("input, select, textarea", form)
                .filter(function() {
                    return !$(this).parent().hasClass("cancel");
                })
                .each( function () {
                    var type = $(this).attr("type");
                    if ((type == "radio")) {
                        if (this.checked) {
                            data[this.name] = this.value;
                        }
                    } else if (type == "checkbox" && $(this).parent().parent().hasClass("input-boolean")) {
                        if (this.checked) {
                            data[this.name] =  $(this).val();
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

            data.formAutosave = "true";
            form.data.saving = true;

            // @todo trigger event before saving
            $.post(form.action, data, function(response, textStatus) {
                now = new Date();

                form.data.lastsave = now.getTime();
                form.data.saving = false;
                //@todo trigger events whether the autosave was successful or not
            });
        };
        form.data.changed = function(saveImmediately) {
            now = new Date();
            clearTimeout(form.data.timer);

            if (!form.data.saving) {
                if (saveImmediately || now.getTime() - form.data.lastsave > saveInterval) {
                    form.data.autosave();
                } else {
                    setTimeout(function() {
                        form.data.changed();
                    }, saveInterval);
                }
            }
        };

        $("input, select, textarea", form).change( function() {
            form.data.changed(true);
        });
        // only for textarea and richtext
        $("textarea", form).keyup( function() {
            form.data.changed();
        });
    }
    // }}}

    // {{{ add tap-events on labels for chackboxes and radiobuttons on iPhone/iPad
    $('input:checkbox, input:radio', form).each(function() {
        var $label = $(this).parents("label");

        $label.bind('click', function(e) {
            // this seems to be enough to fire the default click event on iOS -> no additional action necessary
        });
    });
    // }}}

    // {{{ add handlers to textarea
    $('.input-textarea', form).each(function() {
        var options = $.parseJSON($(this).attr('data-textarea-options'));
        var $textarea = $("textarea", this);
        var $textareaSize = $("<textarea class=\"textarea-size\"></textarea>").appendTo($textarea.parent()).height("auto").css({
            position: "absolute",
            left: "-10000px"
        });

        if (options.autogrow && !$.browser.msie) {
            $textarea.autogrow = function() {
                $textareaSize[0].value = $textarea[0].value;

                // set new height
                var newHeight = $textareaSize[0].scrollHeight + 30;
                $textarea.height(newHeight);
            };
            $textarea.mouseup(function() {
            }).keyup( function() {
                $textarea.autogrow();
            }).keydown( function() {
                $textarea.autogrow();
            }).scroll( function() {
                $textarea.autogrow();
            }).autogrow();
        }
    });
    // }}}
    // {{{ add richtext-editor to richtext inputs
    $('.input-richtext', form).each(function() {
        var options = $.parseJSON($(this).attr('data-textarea-options')) || $.parseJSON($(this).attr('data-richtext-options'));
        var $textarea = $("textarea", this);
        $textarea.depageEditor(options);
    });
    // }}}

    // {{{ focus first input with autofocus
    $('input[autofocus]:first', form).focus();
    // }}}

    // {{{ add missing "http://" to url inputs
    $("input[type='url']", form).change( function() {
        if (this.value !== "" && !this.value.match(/[a-z][\w\-]+:\/\/.*/)) {
            this.value = "http://" + this.value;
        }
    });
    // }}}
}
// }}}

$(document).ready(function () {
    $('.depage-form').each( function() {
        setupForm(this);
    });
});

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
