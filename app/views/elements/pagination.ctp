<div class="pagination clearfix">
	<span style="float:left">
	<?php
	echo $this->Paginator->prev('Prev', array('class' => 'button'), null, array('class' => 'button disabled'));
	echo $this->Paginator->next('Next', array('class' => 'button'), null, array('class' => 'button disabled'));
	?></span>
	<span style="float:right;line-height:30px">
		<?php echo $this->Paginator->counter(array('format' => 'Records: %start%-%end% of %count%')); ?>
	</span>
</div>