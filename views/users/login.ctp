<?php
$this->Html->script('login', array('inline' => false));
$this->Js->buffer('CORE.initLogin()');
?>
<div class="grid_6 prefix_3 suffix_3">
	<div id="logo">
		<?php echo $this->Html->image('logo.png', array('alt' => Core::read('general.site_name_tagless'))); ?>
	</div>
	<?php
	echo $this->Form->create('User');
	?>
	<div id="login-form" class="clearfix">
		<?php
		echo $this->Form->input('username', array(
			'size' => 31,
			'div' => 'input text showhide'
		));
		echo $this->Form->input('password', array(
			'size' => 31,
			'div' => 'input password showhide'
		));
		?>
	</div>
	<div id="login-info">
		<?php
		echo $this->Form->input('remember_me', array(
			'type' => 'checkbox',
			'label' => 'Forget me not'
		));
		echo ' | ';
		echo $this->Html->link('Trouble logging in?', array('action' => 'forgot_password'), array('rel' => 'modal-none'));
		echo ' | ';
		echo $this->Html->link('Sign Up', array('action' => 'register'), array('rel' => 'modal-none'));
		echo $this->Form->end('Login');
		?>
	</div>
	<div style="margin-top: 30px">
		<iframe src="http://player.vimeo.com/video/36054843?title=0&amp;byline=0&amp;portrait=0&amp;color=ffffff" width="460" height="259" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
	</div>
</div>