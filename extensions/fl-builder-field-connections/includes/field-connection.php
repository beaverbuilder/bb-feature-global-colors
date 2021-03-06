<div class="fl-field-connections-toggle">
	<i class="fas fa-plus-circle"></i>
</div>
<div class="fl-field-connection<?php if ( $connection ) {
	echo ' fl-field-connection-visible';} ?>"
	<?php
	if ( $form ) {
		echo ' data-form="' . $form . '"';}
	?>
>

	<div class="fl-field-connection-content">
		<div style="background-color: <?php echo FLBuilderColor::hex_or_rgb( FLBuilderCoreFieldConnections::get_color( $connection->property ) ); ?>" class="fl-field-color-preview"></div>
		<div class="fl-field-connection-label"><?php FLBuilderCoreFieldConnections::render_label( $connection ); ?></div>
		<i class="fl-field-connection-edit fas fa-wrench"></i>
		<i class="fl-field-connection-remove fas fa-times"></i>
	</div>
</div>
<input class="fl-field-connection-value" type="hidden" name="connections[][<?php echo $name; ?>]" value='<?php if ( $connection ) { echo json_encode( $connection ); } // phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace ?>' />
<script> FLThemeBuilderFieldConnections._menus['<?php echo $name; ?>'] = <?php echo json_encode( $menu_data ); ?></script>
