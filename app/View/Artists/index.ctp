<h1>Artists</h1>
<div class="well well-small">
<a href="<?php echo $this->Html->url(array('action'=>'add'))?>" class="btn btn-default">
  <span class="glyphicon glyphicon-cloud-download"></span> Fetch artist
</a>

</div>

<?php echo $this->BootstrapHtml->paginator(); ?>
<ul class="list-group">
	<?php foreach($artists as $artist):?>
		<li class="list-group-item">
			<?php echo $this->Html->link($artist['Artist']['name'], array('controller'=>'artists', 'action'=>'view', $artist['Artist']['id']))?>
			<span class="badge pull-right"><?php echo sizeof($artist['Listener']); ?><span>
		</li>
	<?php endforeach;?>
</ul>

<?php echo $this->BootstrapHtml->paginator(); ?>