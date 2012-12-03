<div class="comments">
    <h2>Comments</h2>
    <?php foreach ($this->comments as $comment) { ?>
        <?php 
            //var_dump($comment); 
            $id = "comment-{$comment->id}";
        ?>
        <article class="comment" itemprop="comment" itemscope itemtype="http://schema.org/UserComments" id="<?php html::t($id); ?>">
            <link itemprop="url" href="#<?php html::t($id); ?>">
            <footer>
                <p><span itemprop="creator" itemscope itemtype="http://schema.org/Person">
                    <span itemprop="name"><?php html::t($comment->author_name); ?></span>
                    <?php if(!empty($comment->author_url)) { ?>
                        <a href="<?php html::t($comment->author_url); ?>" rel="nofollow" itemprop="url"><?php html::t($comment->author_url); ?></a>
                    <?php } ?>
                </span></p>
                <p><time itemprop="commentTime" datetime="<?php html::t($comment->date); ?>"><?php html::t($comment->date); ?></time></p>
            </footer>
            <p><?php html::e($comment->getCommentHtml()); ?></p>
        </article>
    <?php } ?>
    <?php html::e($this->commentForm); ?>
</div>
<?php // vim:set ft=php sw=4 sts=4 fdm=marker et :
