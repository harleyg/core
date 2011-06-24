<h1><?php __('Invitations');?></h1>
<div class="invitations content-box">
	<table class="datatable" cellpadding="0" cellspacing="0">
		<?php
			$i = 0;
			foreach ($invitations as $invitation):
				$class = null;
				if ($i++ % 2 != 0) {
					$class = ' class="altrow"';
				}
			?>
			<tr<?php echo $class;?>>
				<td>
					<?php echo $invitation['Invitation']['body']; ?>
				</td>
				<td>
					<?php
					$icon = $this->element('icon', array('icon' => 'confirm'));
					echo $this->Js->link($icon, array('controller' => 'invitations', 'action' => 'confirm', $invitation['Invitation']['id'], 1), array(
						'success' => 'CORE.update("content");'
					));
					?>
				</td>
			</tr>
			<?php endforeach; ?>
	</table>
	<p><?php echo $this->element('pagination'); ?></p>
</div>