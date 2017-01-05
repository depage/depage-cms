<div class="about">
    <nav>
        <?php foreach($this->info as $head => $info) { ?>
            <li><a <?php self::attr([
                'href' => str_replace(" ", "-", "info/#category-$head"),
            ]); ?>><?php self::t($head); ?></a></li>
        <?php } ?>
    </nav>
    <?php foreach($this->info as $head => $info) { ?>
        <table <?php self::attr([
            'class' => "info",
            'id' => str_replace(" ", "-", "category-$head"),
        ]); ?>>
            <caption colspan="2"><?php self::e($head); ?></caption>
            <?php foreach($info as $key => $val) { ?>
                <tr>
                    <th><?php self::e($key); ?></th>
                    <td><?php self::e($val); ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</div>

<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
