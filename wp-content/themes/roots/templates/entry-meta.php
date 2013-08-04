<ul class="unstyled inline blog-info">
    <li><i class="icon-calendar"></i> <time class="updated" datetime="<?php echo get_the_time('c'); ?>" pubdate><?php echo get_the_date(); ?></time></li>
    <li><i class="icon-pencil"></i> <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" rel="author" class="fn"><?php echo get_the_author(); ?></a></li>
    <li><i class="icon-comments"></i> <a href="<?php the_permalink(); ?>#comments"><?php comments_number(); ?></a></li>
</ul>
<?php the_tags('<ul class="unstyled inline blog-tags"><li><i class="icon-tags"></i> ', "\n", '</li></ul>'); ?>