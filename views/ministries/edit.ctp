<span class="breadcrumb editing"><?php
$icon = $this->element('icon', array('icon' => 'delete'));
echo $this->Html->link($icon, array('action' => 'view', 'Ministry' => $this->passedArgs['Ministry']), array('escape' => false, 'class' => 'no-hover'));
?>Editing<?php echo $this->Html->image('../assets/images/edit-flag-right.png'); ?></span>
<h1><?php echo $this->data['Ministry']['name'].$this->Formatting->flags('Ministry', $this->data); ?></h1>

<div class="ministries core-tabs">
<?php
if (!empty($revision)) {
	$changes = array_diff_assoc($revision, $this->data['Ministry']);
}

if ($revision && !empty($changes)): ?>
<div id="change" class="message change">
	There is a pending change for this ministry
	<?php
	echo $this->Permission->link('History', array('action' => 'history','Ministry' => $this->data['Ministry']['id']),array('data-core-modal' => 'true', 'class' => 'button')
	);
	?>
</div>
<?php endif; ?>
	<ul>
		<li><a href="#ministry-information">Details</a></li>
		<li><a href="#ministry-leaders">Leaders</a></li>
		<li><a href="#ministry-roles">Roles</a></li>
		<li><a href="#ministry-attachments">Attachments</a></li>
	</ul>

	<div class="content-box clearfix">

		<div id="ministry-information">
			<?php
			echo $this->Form->create(array(
				'url' => $this->passedArgs,
				'inputDefaults' => array(
					'empty' => true
				)
			));
			?>
			<fieldset>
				<legend><?php printf(__('Edit %s', true), __('Ministry', true)); ?></legend>
			<?php
				echo $this->Form->input('id');
				echo $this->Form->input('name');
				echo $this->Form->input('description', array(
					'type' => 'textarea',
					'cols' => 100,
					'value' => $this->data['Ministry']['description'],
					'escape' => false
				));
				echo $this->Form->input('parent_id', array(
					'options' => $ministries,
					'escape' => false,
					'empty' => true,
					'label' => 'Parent Ministry'
				));
				echo $this->Form->input('campus_id');
				echo $this->Form->input('private');
				echo $this->Form->input('active');
			?>
			</fieldset>
			<div style="clear:both"><?php echo $this->Js->submit('Save', $defaultSubmitOptions); ?></div>
			<?php echo $this->Form->end(); ?>
		</div>
		<?php
		$url = Router::url(array(
			'controller' => 'ministry_leaders',
			'action' => 'index',
			'Ministry' => $this->data['Ministry']['id'],
			'User' => $activeUser['User']['id']
		));
		?>
		<div id="ministry-leaders" data-core-update-url="<?php echo $url; ?>">
			<?php
			echo $this->requestAction($url, array(
				'renderAs' => 'ajax',
				'bare' => false,
				'return',
				'data' => null,
				'form' => array('data' => null)
			));
			?>
		</div>
		<?php
		$url = Router::url(array(
			'controller' => 'roles',
			'action' => 'index',
			'Ministry' => $this->data['Ministry']['id'],
			'User' => $activeUser['User']['id']
		));
		?>
		<div id="ministry-roles" data-core-update-url="<?php echo $url; ?>">
			<?php
				echo $this->requestAction($url, array(
					'renderAs' => 'ajax',
					'return',
					'data' => null,
					'form' => array('data' => null)
				));
				?>
		</div>
		<div id="ministry-attachments">
			<?php
			$url = Router::url(array(
				'controller' => 'ministry_images',
				'action' => 'index',
				'Ministry' => $this->data['Ministry']['id'],
				'User' => $activeUser['User']['id']
			));
			?>
			<div id="ministry-images" data-core-update-url="<?php echo $url; ?>">
				<?php
				echo $this->requestAction($url, array(
					'renderAs' => 'ajax',
					'bare' => false,
					'return',
					'data' => null,
					'form' => array('data' => null)
				));
				?>
			</div>
		</div>
	</div>

<?php
$this->Js->buffer('CORE.wysiwyg("MinistryDescription");');
?>
</div>