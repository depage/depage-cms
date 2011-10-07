<?php 
    $form = new depage\htmlform\htmlform("userprofile_edit", array(
        'jsautosave' => "true",
    ));

    // define formdata
    $form->addText("name", array(
        'defaultValue' => $this->user->fullname,
    ));
    $form->addEmail("email", array(
        'defaultValue' => $this->user->email,
    ));
    $form->addPassword("password", array(
    ));

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
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :
