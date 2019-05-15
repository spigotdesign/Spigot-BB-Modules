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
<?php
$photo    = $module->get_data();
$classes  = $module->get_classes();
$src      = $module->get_src();
$link     = $module->get_link();
$alt      = $module->get_alt();
$attrs    = $module->get_attributes();
$filetype = pathinfo( $src, PATHINFO_EXTENSION );
$rel      = $module->get_rel();
?>


<div class='spigot-hover-card' style="background-image: url(<?php echo $src; ?>);">

	<a href="<?php echo $settings->link; ?>"></a>

	<div class="spigot-hover-card__content">

		<header class="entry__header">
			<h2><?php echo esc_attr( $settings->heading ); ?></h2>
		</header>

		<div class="entry__summary">
			<?php global $wp_embed;

			echo wpautop( $wp_embed->autoembed( $settings->text ) );

			?>
		</div>

	</div>

</div>

