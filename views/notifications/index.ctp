<h1><?php __('Notifications');?></h1>
<div class="notifications">
<?php
	echo $this->MultiSelect->create();
?>
	<table class="datatable" cellpadding="0" cellspacing="0">
		<thead>
			<?php
			$links = array();
			$links[] =array(
				'title' => 'Mark as read',
				'url' => array(
					'controller' => 'notifications',
					'action' => 'read',
					0,
					1
				),
				'options' => array(
					'data-core-ajax' => 'true'
				)
			);
			$links[] =array(
				'title' => 'Mark as unread',
				'url' => array(
					'controller' => 'notifications',
					'action' => 'read',
					0,
					0
				),
				'options' => array(
					'data-core-ajax' => 'true'
				)
			);
			$links[] = array(
				'title' => 'Delete',
				'url' => array(
					'controller' => 'notifications',
					'action' => 'delete',
					0
				),
				'options' => array(
					'data-core-ajax' => 'true'
				)
			);
			echo $this->element('multiselect', array(
				'colCount' => 2,
				'checkAll' => true,
				'links' => $links
			));
			?>
		</thead>
		<tbody>
			<?php
			$i = 0;
			foreach ($notifications as $notification):
				$class = null;
				if ($i++ % 2 != 0) {
					$class = ' class="altrow"';
				}
			?>
			<tr<?php echo $class;?> id="notification-<?php echo $notification['Notification']['id'];?>">
				<td><?php echo $this->MultiSelect->checkbox($notification['Notification']['id']); ?></td>
				<td><?php
				$class = 'notification ' . ($notification['Notification']['read'] ? 'read' : 'unread');
				$style = 'padding-left:5px';
				echo $this->Html->tag('span', $notification['Notification']['body'], compact('class', 'style'));
				?></td>
			</tr>
			<?php
			endforeach;
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="2">
					<?php echo $this->element('pagination'); ?>
				</td>
			</tr>
		</tfoot>
	</table>
<?php
echo $this->MultiSelect->end();
?>
</div>