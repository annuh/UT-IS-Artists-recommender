<h1>Add artist</h1>

<?php echo $this->BootstrapForm->create('Artist')?>
<?php echo $this->BootstrapForm->hidden('fetched', array('value'=>1))?>
<?php echo $this->BootstrapForm->input('name', array('label'=>'Artist'));?>
<?php echo $this->BootstrapForm->end('Fetch top fans');?>