<?php
$this->Paginator->options(array(
    'updateable' => 'parent'
));
?>
<h1><?php __('Payment Options');?></h1>
<div class="paymentOptions">
	<table cellpadding="0" cellspacing="0" class="datatable">
		<thead>
			<tr>
				<th><?php echo $this->Paginator->sort('name');?></th>
				<th><?php echo $this->Paginator->sort('total');?></th>
				<th><?php echo $this->Paginator->sort('deposit');?></th>
				<th><?php echo $this->Paginator->sort('childcare');?></th>
				<th><?php echo $this->Paginator->sort('account_code');?></th>
				<th><?php echo $this->Paginator->sort('tax_deductible');?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		$i = 0;
		foreach ($paymentOptions as $paymentOption):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' altrow';
			}
		?>
		<tr class="core-iconable<?php echo $class;?>">
			<td><?php echo $paymentOption['PaymentOption']['name']; ?>&nbsp;</td>
			<td><?php echo $this->Formatting->money($paymentOption['PaymentOption']['total']); ?>&nbsp;</td>
			<td><?php echo $this->Formatting->money($paymentOption['PaymentOption']['deposit']); ?>&nbsp;</td>
			<td><?php echo $this->Formatting->money($paymentOption['PaymentOption']['childcare']); ?>&nbsp;</td>
			<td><?php echo $paymentOption['PaymentOption']['account_code']; ?>&nbsp;</td>
			<td><?php echo $this->SelectOptions->booleans[$paymentOption['PaymentOption']['tax_deductible']]; ?>&nbsp;</td>
			<td>
				<div class="core-icon-container">
					<?php echo $this->Html->link($this->element('icon', array('icon' => 'edit')), array('action' => 'edit', $paymentOption['PaymentOption']['id']), array('rel' => 'modal-paymentOptions', 'escape' => false)); ?>
					<?php 
					echo $this->Html->link($this->element('icon', array('icon' => 'delete')), array('action' => 'delete', $paymentOption['PaymentOption']['id']), array('id' => 'delete_btn_'.$paymentOption['PaymentOption']['id'], 'escape' => false));
					$this->Js->buffer('CORE.confirmation("delete_btn_'.$paymentOption['PaymentOption']['id'].'","Are you sure you want to delete this payment option?", {update:"paymentOptions"});');
					?>
				</div>
			</td>
		</tr>
	<?php endforeach; ?>
		</tbody>
	</table>
	<?php
	echo $this->element('icon', array('icon' => 'add')).$this->Html->link('Add Payment Option', array('action' => 'add', 'Involvement' => $involvementId), array('rel'=>'modal-paymentOptions'));
	echo $this->element('pagination');
	?>
</div>