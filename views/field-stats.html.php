<h1><?php printf(__('Stats for field "%s"', 'datatensai-cf7'), self :: prettify($field->name));?></h1>

<table class="widefat">
	<thead>
		<tr>
			<th><?php _e('Answer', 'datatensai-cf7');?></th><th><?php _e('Count', 'datatensai-cf7');?></th><th><?php _e('% of all', 'datatensai-cf7');?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($answers as $ans => $cnt):
			if(empty($class)) $class = 'alternate';
			else $class = '';?>
			<tr class="<?php echo $class;?>">
				<td><?php echo $ans ? esc_atr(stripslashes($ans)) : __('[Not answered]', 'datatensai-cf7');?></td>
				<td><?php echo $cnt;?></td>
				<td><?php echo $total ? round(100 * $cnt / $total) : 0; ?>%</td>
			</tr>
		<?php endforeach;?>
	</tbody>
</table>

<p><a href="#" onclick="jQuery('#barChart').show();jQuery('#pieChart').hide();return false;"><?php _e('Bar Chart', 'datatensai-cf7');?></a>
| <a href="#" onclick="jQuery('#barChart').hide();jQuery('#pieChart').show();return false;"><?php _e('Pie Chart', 'datatensai-cf7');?></a></p>

<!-- bar chart -->
<canvas id="barChart" width="300" height="250"></canvas>
<script>
var ctx = document.getElementById('barChart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php foreach($answers as $ans => $cnt): echo "'".($ans ? esc_attr(stripslashes($ans)) : __('[Not answered]', 'datatensai-cf7'))."',"; endforeach;?>],
        datasets: [{
            label: '<?php _e("# of Answers", 'datatensai-cf7');?>',
            data: [<?php foreach($answers as $ans => $cnt): echo $cnt.','; endforeach;?>],
            backgroundColor: [
                <?php $i = 0; 
                foreach($answers as $ans):
                	echo "'".esc_attr($colors[$i])."',";
                  $i++;
                  if($i >= count($colors)) $i = $i % count($colors);
                endforeach; ?>
            ],
            borderColor: [
                <?php $i = 0; 
                foreach($answers as $ans):
                	echo "'".esc_attr($bcolors[$i])."',";
                  $i++;
                  if($i >= count($bcolors)) $i = $i % count($bcolors);
                endforeach; ?>
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<!-- bar chart -->
<canvas id="pieChart" width="200" height="200" style="display: none;"></canvas>
<script>
var pie = document.getElementById('pieChart').getContext('2d');
var pieChart = new Chart(pie, {
    type: 'pie',
    data: {
        labels: [<?php foreach($answers as $ans => $cnt): echo "'".($ans ? esc_attr(stripslashes($ans)) : __('[Not answered]', 'datatensai-cf7'))."',"; endforeach;?>],
        datasets: [{
            label: '<?php _e("# of Answers", 'datatensai-cf7');?>',
            data: [<?php foreach($answers as $ans => $cnt): echo $cnt.','; endforeach;?>],
            backgroundColor: [
                <?php $i = 0; 
                foreach($answers as $ans):
                	echo "'".esc_attr($colors[$i])."',";
                  $i++;
                  if($i >= count($colors)) $i = $i % count($colors);
                endforeach; ?>
            ],           
            borderWidth: 1
        }]
    }
});
</script>