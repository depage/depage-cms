<?php if (count($this->notifications) > 0) { ?>
    <script>
        <?php
            foreach ($this->notifications as $n) {
                $action = "";
                $duration = "";
                if (!empty($n->options['link']))  {
                    $action = "onClick: function() {
                        window.location = " . json_encode($n->options['link']) . ";
                    },";
                    $duration = "duration: 10000,";
                }
                echo("$.depage.growl(" . json_encode($n->title) . ", {
                    message: " . json_encode($n->message) . ",
                    $action
                    $duration
                    backend: 'html'
                });");
            }
        ?>
    </script>
<?php } ?>
<?php
/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
