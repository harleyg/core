<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo Core::read('general.site_name_tagless').' '.Core::read('version').' :: '.$title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		// vendor css
		echo $this->Html->css('jquery.wysiwyg');
		echo $this->Html->css('fullcalendar');

		// CORE css
		echo $this->Html->css('reset');
		echo $this->Html->css('960');
		echo $this->Html->css('font-face');
		echo $this->Html->css('menu');		
		echo $this->Html->css('jquery-ui');
		echo $this->Html->css('styles');
		echo $this->Html->css('tables');
		if(preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])) {
			echo $this->Html->css('ie');
		}		
		echo $this->Html->css('calendar');

		// google cdn scripts
		echo $this->Html->script('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.js');
		echo $this->Html->script('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.js');
		echo $this->Html->script('http://maps.google.com/maps/api/js?sensor=false');
		
		// vendor scripts
		echo $this->Html->script('jquery.plugins/jquery.form');
		echo $this->Html->script('jquery.plugins/jquery.qtip');
		echo $this->Html->script('jquery.plugins/jquery.cookie');
		echo $this->Html->script('jquery.plugins/jquery.wysiwyg');
		echo $this->Html->script('jquery.plugins/jquery.equalheights');
		echo $this->Html->script('jquery.plugins/jquery.fullcalendar');
		
		// CORE scripts
		echo $this->Html->script('functions');
		echo $this->Html->script('global');
		echo $this->Html->script('ui');
		echo $this->Html->script('form');
		echo $this->Html->script('navigation');
		
		// setup
		$this->Js->buffer('CORE.init()');
		echo $this->Js->writeBuffer();
		echo $scripts_for_layout;
	?>
</head>
<body>
	<div class="container_12" id="wrapper">
		<div class="container_12 clearfix" id="header">
			<div class="grid_10 main-nav-menu" id="primary">
				<ul>
					<li id="nav-home"><?php echo $this->Html->link('☻', '/'); ?></li>
					<li id="nav-profile"><?php echo $this->Html->link('Profile', array('controller' => 'profiles', 'action' => 'view', 'User' => $activeUser['User']['id'])); ?>
						<ul>
							<li>
								<?php
										if (count($activeUser['Image']) > 0) {
											echo '<div class="profile-image">';
											$path = 's'.DS.$activeUser['Image'][0]['dirname'].DS.$activeUser['Image'][0]['basename'];
											echo $this->Media->embed($path, array('restrict' => 'image'));
											echo '</div>';
										}
								?>
								<div class="profile-information">
									<?php
										echo '<div class="profile-name">'.$activeUser['Profile']['name'].'</div>';
										echo '<div class="profile-address">';
										echo $activeUser['ActiveAddress']['address_line_1'];
										if (!empty($activeUser['ActiveAddress']['address_line_2'])) {
											echo '<br />'.$activeUser['ActiveAddress']['address_line_2'];
										}
										echo '<br />'.$activeUser['ActiveAddress']['city'].', '.$activeUser['ActiveAddress']['state'].' '.$activeUser['ActiveAddress']['zip'];
										echo '<div>'.$this->Html->link('Change', array('controller' => 'profiles', 'action' => 'view', 'User' => $activeUser['User']['id']));
										echo '</div>';
										echo '</div>';
									?>
								</div>
								<div style="clear:both" />
							</li>
							<li class="hover-row"><?php echo $this->Html->link('My Involvement', array('controller' => 'rosters', 'action' => 'involvement', 'User' => $activeUser['User']['id'])); ?></li>
							<li class="hover-row"><?php echo $this->Html->link('My Household', array('controller' => 'households', 'User' => $activeUser['User']['id'])); ?></li>
							<li class="hover-row"><?php echo $this->Html->link('My Payments', array('controller' => 'payments', 'User' => $activeUser['User']['id'])); ?></li>
						</ul>
					</li>
					<li id="nav-notifications"><?php
					$new = count(Set::extract('/Notification[read=0]', $activeUser['Notification']));
					echo $this->Html->link('Notifications', array('controller' => 'notifications', 'action' => 'index'));
					if ($new > 0) {
						echo $this->Html->tag('span', $new, array('class' => 'notification-count'));
					}
					?>
						<ul>
							<?php
								foreach ($activeUser['Alert'] as $alert) {
									echo '<li>';
									$name = $this->Html->tag('div', $alert['Alert']['name'], array('class' => 'alert-name'));
									$desc = $this->Html->tag('div', $this->Text->truncate($alert['Alert']['description'], 100), array('class' => 'alert-description'));
									echo $this->Html->link($name.$desc, array('controller' => 'alerts', 'action' => 'view', $alert['Alert']['id']), array('escape' => false));
									echo '</li>';
								}

								foreach ($activeUser['Notification'] as $notification) {
									$class = $notification['Notification']['read'] ? 'read' : 'unread';
									echo '<li id="notification-'.$notification['Notification']['id'].'" class="notification"><p class="'.$class.'">';
									echo $this->Text->truncate($notification['Notification']['body'], 100, array('html' => true));
									echo '</p>';
									echo $this->Html->link('[X]', array(
										'controller' => 'notifications',
										'action' => 'delete',
										$notification['Notification']['id']
									), array(
										'class' => 'delete'
									));
									echo '</li>';
								}
								echo '<li id="notification-viewall">';
								echo $this->Html->link('View All Notifications', array('controller' => 'notifications'));
								echo '</li>';
							?>
						</ul>
					</li>
					<li id="nav-ministries">
						<?php
						echo $this->Html->link('Ministries', array('controller' => 'ministries'));
						echo $this->element('menu'.DS.'campus', array('campuses' => $campusesMenu), true);
						?>
					</li>
					<li id="nav-calendar"><?php echo $this->Html->link('Calendar', array('controller' => 'dates', 'action' => 'calendar')); ?>
						<ul>
							<li>
								<?php echo $this->element('calendar'); ?>
							</li>
						</ul>
					</li>
					<?php if (Configure::read()): ?>
					<li><?php echo $this->Html->link('Debugging', array('controller' => 'reports', 'action' => 'index')); ?>
						<ul><li><?php
					echo $this->Html->link('Report a bug on this page', array('controller' => 'sys_emails', 'action' => 'bug_compose'), array('rel' => 'modal-none'));
					echo $this->Html->link('View activity logs', array('controller' => 'logs', 'action' => 'index'), array('rel' => 'modal-none'));
					?></li></ul>
					</li>
					<?php endif; ?>
					<li id="nav-search">
						<?php
							echo $this->Form->create('Search', array(
								'url' => array(
									'controller' => 'searches',
									'action' => 'index'
								),
								'inputDefaults' => array(
									'div' => false
								)
							));
							echo $this->Form->input('Search.query', array(
								'label' => false,
								'value' => 'Search CORE',
								'size' => 30,
								'class' => 'search-out'
							));
							echo $this->Form->button(
								$this->Html->tag('span', '&nbsp;', array('class' => 'ui-button-icon-primary ui-icon ui-icon-search')),
								array(
									'escape' => false,
									'class' => 'reverse'
								)
							);
							echo $this->Form->end();
						?>
					</li>
				</ul>
			</div>
			<div class="grid_2" id="secondary">
				<?php
				echo $this->Html->link('View API', array('controller' => 'api_classes', 'plugin' => 'api_generator'));
				echo ' / ';
				echo $this->Html->link('Logout', array('controller' => 'users', 'action' => 'logout'));
				?>
			</div>
		</div>
		<div id="content-container" class="container_12 clearfix">
			<div id="content" class="grid_10 prefix_1 suffix_1">
				<?php echo $this->Session->flash('auth'); ?>
				<?php echo $this->Session->flash(); ?>

				<?php echo $content_for_layout; ?>
			</div>
		</div>
		<div id="footer" class="container_12 clearfix">
		</div>
	</div>
</body>
</html>