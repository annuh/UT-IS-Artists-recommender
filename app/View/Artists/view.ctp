<h1><?php echo $artist['Artist']['name']?></h1>


<table>
	<thead>
	<tr>
		<th>Username</th>
		<th>Weight</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach($artist['Fan'] as $fan):?>
	<tr>
		<td><?php echo $this->Html->link($fan['name'], $fan['url'], array('target'=>'_blank'));?></td>
		<td><?php echo $fan['weight'];?></td>
	</tr>
	<?php endforeach;?>
	
	</tbody>
</table>