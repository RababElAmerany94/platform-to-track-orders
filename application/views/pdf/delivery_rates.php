<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>Delivery Rates per Month per DA</title>
	<link rel="stylesheet" href="assets/css/paper.min.css"/>
	<link rel="stylesheet" href="assets/css/print.css"/>
</head>
<body>
<header>
	<h2 class="title">Delivery Rates per Month per DA</h2>
	<?php if (isset($client_name)) { ?>
		<div style="font-size:18px;vertical-align:bottom;"><strong>Client: </strong><?= $client_name ?></div>
	<?php } ?>
	<br><br><br>
</header>

<table class="table table-bordered table-hover">
	<thead>
	<tr>
		<th>Delivery Agent</th>
		<th>Delivered</th>
		<th>No Response</th>
		<th>Cancelled</th>
		<th>Returned</th>
	</tr>
	</thead>
	<tbody>
	<?php for ($i = 0; $i < count($data); $i++) {
		$item = $data[$i];
		?>
		<tr>
			<td style="text-align:left;"><?php echo $item['full_name'] ?></td>
			<td style="text-align:center;"><?php echo round($item['percentage_delivered']) ?>% (<?php echo round($item['total_delivered']) ?>)</td>
			<td style="text-align:center;"><?php echo round($item['percentage_no_response']) ?>% (<?php echo round($item['total_no_response']) ?>)</td>
			<td style="text-align:center;"><?php echo round($item['percentage_cancelled']) ?>% (<?php echo round($item['total_cancelled']) ?>)</td>
			<td style="text-align:center;"><?php echo round($item['percentage_returned']) ?>% (<?php echo round($item['total_returned']) ?>)</td>
		</tr>
	<?php } ?>
	</tbody>
</table>
</body>
</html>