<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
			<?php echo $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		//echo $this->Html->css('cake.generic');
		echo $this->Html->css('bootstrap.min');
		
		echo $this->Html->script('http://code.jquery.com/jquery-1.10.1.min.js');
		echo $this->Html->script('bootstrap.min');

	?>
	<style>
	body { padding-top: 70px; }
	
	</style>
</head>
<body class="container">

	
	<div id="container" class="row">
		<nav role="navigation" class="navbar navbar-default navbar-fixed-top">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <button data-target="#bs-example-navbar-collapse-6" data-toggle="collapse" class="navbar-toggle" type="button">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
			<span class="icon-bar"></span>
          </button>
          <a href="#" class="navbar-brand">IR</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div id="bs-example-navbar-collapse-6" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Home</a></li>
            <li><a href="#">Artists</a></li>
            <li><a href="#">Users</a></li>
          </ul>
        </div><!-- /.navbar-collapse -->
      </nav>
		<aside class="col-md-3">
			<ul class="nav nav-pills nav-stacked">
			  <li class="active"><a href="#">Home</a></li>
			  <li><?php echo $this->Html->link('Artists', array('controller'=>'artists', 'action'=>'index'))?></li>
			  <li><a href="#">Top Fans</a></li>
			</ul>
		
		
		
		</aside>
		<section class="col-md-9">
			<?php echo $content_for_layout; ?>
		</section>
		
		
	</div>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>
