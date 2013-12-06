<div class="row">
	<div class="col-md-2">
		<div class="thumbnail">
			<img src="<?php echo $artist['Artist']['image']?>" alt="<?php echo $artist['Artist']['name']?>">
		</div>
	</div>
	<div class="col-md-10">
		<div class="caption">
			<h3><?php echo $artist['Artist']['name']?></h3>
      	</div>
    </div>
</div>

<div class="well"></div>
<table>
	<thead>
	<tr>
		<th>Username</th>
		<th>Artists</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach($artist['Fan'] as $fan):?>
	<tr>
		<td ><?php echo $this->Html->link($fan['User']['name'], $fan['User']['url'], array('target'=>'_blank'));?></td>
		<td data-user="<?php echo $fan['User']['name']?>" data-user_id="<?php echo $fan['User']['id']?>">
		<?php echo $this->Html->image('loader.gif')?>
		</td>
	</tr>
	<?php endforeach;?>
	
	</tbody>
</table>

<?php echo $this->Html->scriptStart();?>
var url = "<?php echo $this->Html->url(array('controller'=>'artists','action'=>'get_fan_artists'))?>.json";
$("td[data-user]").each(function( index ) {
	var td = $(this);
	var user = $(this).data('user');
	var user_id = $(this).data('user_id');
	var post = {'user':user, 'user_id':user_id};
	$.post(url, post, function(data){
		td.html(data.artists);		
	});
});
<?php echo $this->Html->scriptEnd();?>