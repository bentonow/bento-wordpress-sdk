<h2 class="tvd-card-title"><?php echo esc_html( $this->get_title() ); ?></h2>
<div class="tvd-row">
	<form class="tvd-col tvd-s12">
		<input type="hidden" name="api" value="<?php echo esc_attr( $this->get_key() ) ?>"/>
		
	</form>
</div>
<div class="tvd-card-action">
	<div class="tvd-row tvd-no-margin">
		<div class="tvd-col tvd-s12 tvd-m6">
			<a class="tvd-api-cancel tvd-btn-flat tvd-btn-flat-secondary tvd-btn-flat-dark tvd-full-btn tvd-waves-effect"><?php echo esc_html__( "Cancel", 'thrive-dash' ) ?></a>
		</div>
		<div class="tvd-col tvd-s12 tvd-m6">
			<a class="tvd-api-connect tvd-waves-effect tvd-waves-light tvd-btn tvd-btn-green tvd-full-btn"><?php echo esc_html__( "Connect", 'thrive-dash' ) ?></a>
		</div>
	</div>
</div>

