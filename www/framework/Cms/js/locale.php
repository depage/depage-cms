<?php
    $locales = [];
    $localeDir = __DIR__ . "/../../locale";
    $textdomain = "messages";

    $dirs = glob("$localeDir/*", GLOB_ONLYDIR);

    foreach ($dirs as $dir) {
        $locale = basename($dir);
        $lang = substr($locale, 0, 2);

        bindtextdomain($textdomain, $localeDir);
        bind_textdomain_codeset($textdomain, 'UTF-8');
        textdomain($textdomain);

        putenv('LANGUAGE=' . $locale . ".UTF-8");
        putenv('LC_ALL=' . $locale . ".UTF-8");
        setlocale(LC_ALL, $locale . ".UTF-8");

        $locales[$lang] = [
            "cancel" => _("Cancel"),
            "choose" => _("Choose"),
            "chooseCenter" => _("Choose image center"),
            "chooseCenterHint" => _("Please click on a point on the image to set it's gravitational center:"),
            "chooseCenterExamples" => _("Examples of resulting cropped images:"),
            "chooseFileMessage" => _("Please choose a file"),
            "close" => _("Close"),
            "copy" => _("Copy"),
            "copyUrl" => _("Copy URL to Clipboard"),
            "create" => _("New"),
            "createNew" => _("Create new:"),
            "createNewAtEnd" => _("Create new element at the end"),
            "createNoElements" => _("There are no elements that can be created in this element"),
            "cut" => _("Cut"),
            "delete" => _("Delete"),
            "deleteQuestion" => _("Delete this element now?"),
            "deselectAll" => _("Deselect all"),
            "duplicate" => _("Duplicate"),
            "edit" => _("Edit"),
            "editHelp" => _("Edit the current page in edit interface on the left ←."),
            "editTooltip" => _("Edit the current page"),
            "forceHeightMessage" => _("Height: "),
            "forceWidthMessage" => _("Width: "),
            "layout-left-full" => _("Switch to edit-only layout"),
            "layout-right-full" => _("Switch to preview-only layout"),
            "layout-split" => _("Switch to split layout"),
            "layoutSwitchHelp" => _("Switch layout to: Edit-only, Split-view and Preview-only"),
            "newPost" => _("News- or blog-post"),
            "noCurrentTasks" => _("No current tasks."),
            "ok" => _("Ok"),
            "paste" => _("Paste"),
            "projectFilter" => _("Filter Projects"),
            "reload" => _("Reload"),
            "reloadHelp" => _("Reload page preview"),
            "reloadTooltip" => _("Reload page preview"),
            "rename" => _("Rename"),
            "reset" => _("Reset"),
            "selectAll" => _("Select all"),
            "shareUrl" => _("Mail Link to file"),
            "shareUrlSubject" => _("Link to file"),
            "uploadFinishedCancel" => _("Finished uploading/Cancel"),
            "zoomHelp" => _("Change zoom level of preview."),
        ];
    }

    $javascript = "depageCMSlocale = " . json_encode($locales) . ";";

    file_put_contents(__DIR__ . "/locale.js", $javascript);

// vim:set ft=php sw=4 sts=4 fdm=marker et :
