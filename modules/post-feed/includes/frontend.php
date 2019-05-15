<?php

/**
 * This file should be used to render each module instance.
 * You have access to two variables in this file: 
 * 
 * $module An instance of your module class.
 * $settings The module's settings.
 *
 * Example: 
 */

?>
<?php $post_feed = new WP_Query( 

	array( 
		'post_type' => 'post', 
		'posts_per_page' => $settings->post_items 
		) 
	); ?>
				
	<?php if ( $post_feed->have_posts() ) :?>

		<div class='post-list'>
				
		<?php while ( $post_feed->have_posts() ) : $post_feed->the_post(); ?>

			<article class="post-list__card">

				<a href="<?php the_permalink() ?>"><?php the_post_thumbnail( 'post-thumb', [ 'class' => 'entry__image' ] ) ?></a>

				<header class="entry__header">
					<?php Hybrid\Post\display_title() ?>
				</header>

				<div class="entry__summary">
					<?php $content = get_the_content(); ?>
					<?php echo wp_trim_words( $content , '17' ); ?>
				</div>

				<footer class="entry__footer">
					<?php Hybrid\Post\display_terms( [ 'taxonomy' => 'category' ] ) ?>
					<?php Hybrid\Post\display_date() ?>
				</footer>

			</article>

		<?php endwhile; ?>

		</div>

	<?php endif; wp_reset_query(); ?>
