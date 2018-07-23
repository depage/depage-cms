<ul <?php self::attr([
    'data-colorschemeId' => $this->colorschemeId,
    'data-type' => $this->type,
    'data-palette' => json_encode($this->palette),
]); ?>>
    <?php foreach($this->colorNodes as $node) { ?>
        <li>
            <figure <?php self::attr([
                'class' => "thumb color",
                'data-nodeId' => $node->getAttribute("db:id"),
                'data-name' => $node->getAttribute("name"),
                'data-value' => $node->getAttribute("value"),
            ]); ?>>
                <span <?php self::attr([
                    'class' => "preview",
                    'style' => "background-color: " . $node->getAttribute("value"),
                ]); ?>></span>
                <figcaption>
                    <?php self::t($node->getAttribute("name")); ?>
                </figcaption>
            </figure>
        </li>
    <?php } ?>

</ul>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
