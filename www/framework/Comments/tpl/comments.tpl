<div class="depage-comments">
    <h2><?php
        $c = count($this->comments);
        if ($c > 0) {
            self::t(sprintf(ngettext("%d Comment", "%d Comments", $c), $c));
        } else {
            self::t(_("Comments"));
        }
    ?></h2>
    <?php foreach ($this->comments as $comment) { ?>
        <?php
            $id = "comment-{$comment->id}";
            $date = \depage\datetime\DateTime::createFromFormat("Y-m-d H:i:s", $comment->date);
        ?>
        <article class="comment" itemprop="comment" itemscope itemtype="http://schema.org/UserComments" id="<?php self::t($id); ?>">
            <link itemprop="url" href="#<?php self::t($id); ?>">
            <footer>
                <p>
                <?php if(!empty($comment->author_url)) { ?><a href="<?php self::t($comment->author_url); ?>" rel="nofollow" itemprop="url" target="_blank"><?php } ?>
                    <span itemprop="creator" itemscope itemtype="http://schema.org/Person">
                        <img itemprop="image" src="<?php self::t($comment->getProfileImageUrl()); ?>" class="profile-image">
                        <span itemprop="name"><?php self::t($comment->author_name); ?></span>
                    </span>
                <?php if(!empty($comment->author_url)) { ?></a><?php } ?>
                </p>
                <p class="date"><time itemprop="commentTime" datetime="<?php self::t($comment->date); ?>"><?php self::t($date->getDiffNatural()); ?></time></p>
            </footer>
            <div class="comment-text">
                <?php self::e($comment->getCommentHtml()); ?>
            </div>
        </article>
    <?php } ?>
    <?php self::e($this->commentForm); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
