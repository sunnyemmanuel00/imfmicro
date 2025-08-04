<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of Inquiries</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<table class="table table-bordered table-stripped" id="inquiry-list">
				<colgroup>
					<col width="5%">
					<col width="20%">
					<col width="25%">
					<col width="35%">
					<col width="15%">
				</colgroup>
				<thead>
					<tr>
						<th class="text-center">#</th>
						<th>Date Received</th>
						<th>Sender Details</th>
						<th>Subject / Type</th>
						<th class="text-center">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
						$qry = $conn->query("SELECT * from `inquiries` order by unix_timestamp(date_created) desc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td>
								<p class="m-0 lh-1">
									<small>
										<span class="text-muted">Name: </span><?php echo $row['name'] ?><br>
										<span class="text-muted">Email: </span><?php echo $row['email'] ?><br>
										<span class="text-muted">Phone: </span><?php echo $row['phone'] ?>
									</small>
								</p>
							</td>
							<td>
								<p class="m-0 lh-1">
									<small>
										<span class="text-muted">Type: </span><?php echo $row['type'] ?><br>
										<span class="text-muted">Subject: </span><?php echo $row['subject'] ?>
									</small>
								</p>
							</td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm view_data" data-id="<?php echo $row['id'] ?>">
				                  		<span class="fa fa-eye text-primary"></span> View
				                  </button>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('#inquiry-list').dataTable({
			columnDefs: [
					{ orderable: false, targets: [2,4] }
			],
			order:[0,'asc']
		});
		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle');
		
		$('.view_data').click(function(){
			uni_modal("Inquiry Details","inquiries/view_inquiry.php?id="+$(this).attr('data-id'), "large")
		})
	})
</script>