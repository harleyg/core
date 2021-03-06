<h1>Comments</h1>
<div class="comments">
	<?php
	$i = 0;
	foreach ($comments as $comment):
		$i++;
	?>
	<div class="comment clearfix">
		<div class="comment-image">
		<?php
		if (!empty($comment['Creator']['Image'][0]['id'])) {
			$path = 's'.DS.$comment['Creator']['Image'][0]['dirname'].DS.$comment['Creator']['Image'][0]['basename'];
			echo $this->Media->embed($path, array('restrict' => 'image'));
		}
		?>
		</div>
		<div class="comment-body">
			<div class="comment-title">
				<span class="float:left">
					<?php
					echo $this->Html->link($comment['Creator']['Profile']['name'], array('controller' => 'profiles', 'action' => 'view', 'User' => $comment['Creator']['id']));
					echo ' ('.$comment['Group']['name'].') ';
					echo 'Commented on '.$this->Formatting->date($comment['Comment']['created']);
					?>
				</span>
				<span style="float:right">
					<?php
					echo $this->Html->link('Edit', array('action' => 'edit', 'Comment' => $comment['Comment']['id'], 'User' => $comment['Creator']['id']), array('id' => 'edit_comment_'.$i, 'class' => 'core-icon icon-edit', 'title' => 'Edit', 'data-core-ajax' => 'true'));
					echo $this->Html->link('Delete', array('action' => 'delete', 'Comment' => $comment['Comment']['id'], 'User' => $comment['Creator']['id']), array('id' => 'delete_comment_'.$i, 'class' => 'core-icon icon-delete', 'title' => 'Delete'));
					$this->Js->buffer('CORE.confirmation("delete_comment_'.$i.'", "Are you sure you want to delete this comment?", {update:true});')
					?>
				</span>
			</div>
			<div class="comment-comment">
				<?php echo $comment['Comment']['comment']; ?>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
</div>
<?php echo $this->element('pagination'); ?>
<ul class="core-admin-tabs">
	<?php
	$link = $this->Permission->link('Add comment', array('controller' => 'comments', 'action' => 'add', 'User' => $userId), array(
		'data-core-modal' => 'true',
		'escape' => true
	));
	if ($link) {
		echo $this->Html->tag('li', $link);
	}
	?>
</ul>
