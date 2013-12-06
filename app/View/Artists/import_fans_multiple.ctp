<?php foreach($artists as $artist):?>
<div class="row" data-artist="<?php echo $artist['Artist']['name'];?>">
	<div class="col-md-2">
		<div class="thumbnail">
			<img src="<?php echo $artist['Artist']['image']?>" alt="<?php echo $artist['Artist']['name']?>">
		</div>
	</div>
	<div class="col-md-10">
		<div class="caption">
			<h3><?php echo $artist['Artist']['name']?></h3>
      	</div>
      	<p>Fetching top fans...</p>
      	<div class="progress progress-striped active">
		  <div class="progress-bar"  role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
		    <span class="sr-only">0% Complete</span>
		  </div>
		</div>
		      	
    </div>
</div>
<?php endforeach;?>
<?php echo $this->Html->scriptStart();?>
var url = "<?php echo $this->Html->url(array('controller'=>'artists','action'=>'get_artist_fans'))?>.json";
var fan_url = "<?php echo $this->Html->url(array('controller'=>'artists','action'=>'get_fan_artists'))?>.json";
$("div[data-artist]").each(function( index ) {
	var p = $(this).find('p');
	var progressbar = $(this).find('.progress-bar');
	var artist = $(this).data('artist');
	var post = {'artist':artist};
	$.post(url, post, function(data){
		p.hide();
		progressbar.show();
		get_fan_artist(progressbar, data.fans);
	});
});

function get_fan_artist(block, fans){
	var total = fans.length;
	$.each( fans, function( index, fan ){
		var post = {'user':fan};
		$.ajax({
			type: 'POST',
			url: fan_url, 
			data: post,
			success: function(data){
				var step = 100/total;
				update_progressbar(block, step);
			},
			async: false
		});		
	});
}

function update_progressbar(bar, step){
	var percent = parseInt(bar.attr('aria-valuenow')) + step;
	bar.width(percent+"%");
	bar.attr('aria-valuenow', percent);
}
	

<?php echo $this->Html->scriptEnd();?>