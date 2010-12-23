<div class="users">
<p>If you choose to change this information, you will automatically logged out and will need to log in using the new details.</p>
<?php
echo $this->Form->create('User', array(
	'default' => false
));
?>
<fieldset>
	<legend>User</legend>
	<?php
	echo $this->Form->input('id');
	echo $this->Form->input('reset', array(
		'type' => 'select',
		'options' => array(
			'password' => 'Password',
			'username' => 'Username',
			'both' => 'Both'			
		),
		'label' => 'What do you want to change?'
	));			
	echo $this->Form->input('username');
	if ($needCurrentPassword) {
		echo $this->Form->input('current_password', array('type' => 'password'));
	}
	echo $this->Form->input('password', array(
		'label' => 'New Password'
	));
	echo $this->Form->input('confirm_password', array(
		'type' => 'password',
		'label' => 'Confirm New Password'
	));
	?>
</fieldset>
<?php
$defaultSubmitOptions['success'] = 'CORE.successForm(event, data, textStatus, {autoUpdate:"failure", success:onComplete})';
echo $this->Js->submit('Submit', $defaultSubmitOptions);
echo $this->Form->end();
$this->Js->buffer('function onComplete() {
	redirect("'.Router::url(array('action' => 'logout', 'message' => 'Please log in with your new credentials.')).'");
}');
$this->Js->buffer('$("#UserReset").bind("change", function() {
	switch ($(this).val()) {
		case "password":
		$("#UserUsername").parent().hide();
		$("#UserCurrentPassword").parent().show();
		$("#UserPassword").parent().show();
		$("#UserConfirmPassword").parent().show();
		break;
		case "username":
		$("#UserUsername").parent().show();
		$("#UserCurrentPassword").parent().hide();
		$("#UserPassword").parent().hide();
		$("#UserConfirmPassword").parent().hide();
		break;
		case "both":
		$("#UserUsername").parent().show();
		$("#UserCurrentPassword").parent().show();
		$("#UserPassword").parent().show();
		$("#UserConfirmPassword").parent().show();
		break;
	}
});');
$this->Js->buffer('$("#UserReset").change()');
?>
</div>