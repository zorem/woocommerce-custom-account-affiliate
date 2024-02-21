<?php
/**
 * Template name: WooCommerce - My Account
 *
 * This template adds My account to the sidebar.
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.16.0
 */

get_header('account'); ?>

<?php do_action( 'flatsome_before_page' ); ?>

<?php //wc_get_template( 'myaccount/header.php' ); ?>

<?php if ( is_user_logged_in() ) { ?>
<?php } else { ?>
	<div class="page-wrapper my-account mb">
		<?php while ( have_posts() ) : the_post(); ?>

			<?php the_content(); ?>

		<?php endwhile; // end of the loop. ?>
	</div>
<?php } ?>

<?php do_action( 'flatsome_after_page' ); ?>

<?php get_footer('account'); ?>
