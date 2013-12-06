<h1>Suggestions</h1>

<?php echo $this->Form->create('Rating');?>
<div class="row">
<?php foreach($artists as $artist):?>
<div class="col-md-4">
<?php echo $this->Form->input($artist['Artist']['id'].'.grade', array('label' => $artist['Artist']['name']));?>
</div>.
<?php endforeach;?>
</div>
<?php echo $this->Form->end('Send');?>