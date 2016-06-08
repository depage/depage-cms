<?php
    $form = new Depage\HtmlForm\HtmlForm("userprofile_edit", [
        'jsautosave' => "true",
    ]);

    // define formdata
    $form->addText("name", [
        'defaultValue' => $this->user->fullname,
    ]);
    $form->addEmail("email", [
        'defaultValue' => $this->user->email,
    ]);
    $form->addPassword("password", [
    ]);

    // process form
    $form->process();

    if ($form->validate()) {
        // saving formdata
        echo("<p>saving</p>");
        var_dump($form->getValues());

        //$form->clearSession();
    }

    echo($form);
?>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
