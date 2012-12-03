<div class="depage-comments">
    <h2>Comments</h2>
    <?php foreach ($this->comments as $comment) { ?>
        <?php 
            //var_dump($comment); 
            $id = "comment-{$comment->id}";
        ?>
        <article class="comment" itemprop="comment" itemscope itemtype="http://schema.org/UserComments" id="<?php html::t($id); ?>">
            <link itemprop="url" href="#<?php html::t($id); ?>">
            <footer>
                <img itemprop="img" src="<?php html::t($comment->getProfileImageUrl()); ?>">
                <p><span itemprop="creator" itemscope itemtype="http://schema.org/Person">
                    <span itemprop="name"><?php html::t($comment->author_name); ?></span>
                    <?php if(!empty($comment->author_url)) { ?>
                        <a href="<?php html::t($comment->author_url); ?>" rel="nofollow" itemprop="url"><?php html::t($comment->author_url); ?></a>
                    <?php } ?>
                </span></p>
                <p><time itemprop="commentTime" datetime="<?php html::t($comment->date); ?>"><?php html::t($comment->date); ?></time></p>
            </footer>
            <?php html::e($comment->getCommentHtml()); ?>
        </article>
    <?php } ?>
    <?php html::e($this->commentForm); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
