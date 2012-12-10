<div class="depage-comments">
    <h2><?php html::t(_("Comments")); ?></h2>
    <?php foreach ($this->comments as $comment) { ?>
        <?php 
            $id = "comment-{$comment->id}";
            $date = \depage\datetime\DateTime::createFromFormat("Y-m-d H:i:s", $comment->date);
        ?>
        <article class="comment" itemprop="comment" itemscope itemtype="http://schema.org/UserComments" id="<?php html::t($id); ?>">
            <link itemprop="url" href="#<?php html::t($id); ?>">
            <footer>
                <p>
                <?php if(!empty($comment->author_url)) { ?><a href="<?php html::t($comment->author_url); ?>" rel="nofollow" itemprop="url" target="_blank"><?php } ?>
                    <span itemprop="creator" itemscope itemtype="http://schema.org/Person">
                        <img itemprop="image" src="<?php html::t($comment->getProfileImageUrl()); ?>" class="profile-image">
                        <span itemprop="name"><?php html::t($comment->author_name); ?></span>
                    </span>
                <?php if(!empty($comment->author_url)) { ?></a><?php } ?>
                </p>
                <p class="date"><time itemprop="commentTime" datetime="<?php html::t($comment->date); ?>"><?php html::t($date->getDiffNatural()); ?></time></p>
            </footer>
            <div class="comment-text">
                <?php html::e($comment->getCommentHtml()); ?>
            </div>
        </article>
    <?php } ?>
    <?php html::e($this->commentForm); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
