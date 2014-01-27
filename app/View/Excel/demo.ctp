<?php 
$artists_select = array();
foreach($artists as $artist){
	$artists_select[$artist['Artist']['id']] = $artist['Artist']['name'];
}
?>

<h1>Suggestions</h1>

<?php echo $this->Form->create('Rating', array('horizontal'=>true));?>

<?php echo $this->Form->input('0.artist_id', array('options'=>$artists_select));?>
<?php echo $this->Form->input('0.grade');?>

<?php echo $this->Form->input('1.artist_id', array('options'=>$artists_select));?>
<?php echo $this->Form->input('1.grade');?>

<?php echo $this->Form->input('2.artist_id', array('options'=>$artists_select));?>
<?php echo $this->Form->input('2.grade');?>

<?php echo $this->Form->end('Send');?>