<script>
    <?php
        foreach ($this->notifications as $n) {
            echo("$.depage.growl(" . json_encode($n->title) . ", {
                message: " . json_encode($n->message) . ",
                backend: 'html'
            });");
        }
    ?>
</script>
<?php
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
