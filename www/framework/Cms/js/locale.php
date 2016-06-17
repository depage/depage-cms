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
            "delete" => _("Delete"),
            "deleteQuestion" => _("Delete now?"),
            "edit" => _("edit"),
            "editHelp" => _("Edit current page in edit interface on the left ←."),
            "layoutSwitchHelp" => _("Switch layout to: Edit-only, Split-view and Preview-only"),
            "projectFilter" => _("Filter Projects"),
            "reload" => _("reload"),
            "reloadHelp" => _("reload page preview"),
            "uploadFinishedCancel" => _("finished uploading/cancel"),
            "zoomHelp" => _("Change zoom level of preview."),
        ];
    }

    $javascript = "depageCMSlocale = " . json_encode($locales) . ";";

    file_put_contents(__DIR__ . "/locale.js", $javascript);

// vim:set ft=php sw=4 sts=4 fdm=marker et :
