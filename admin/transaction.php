<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
.account-info-img{
	width:3em;
	height:3em;
	object-fit:cover;
	object-position:center center;
}
</style>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">List of All Transactions</h3>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<table class="table table-bordered table-stripped" id="transaction-list">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="15%">
					<col width="20%">
					<col width="15%">
					<col width="15%">
					<col width="15%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Date Created</th>
						<th>Transaction Code</th>
						<th>Account</th>
						<th>Type</th>
						<th>Amount</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
					// The original query from your previous code
					$qry = $conn->query("SELECT t.*,a.account_number, concat(a.lastname,', ',a.firstname,' ',a.middlename) as `name` FROM `transactions` t inner join `accounts` a on a.id = t.account_id order by unix_timestamp(t.date_created) desc ");
					if ($qry) {
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td><?php echo $row['ref_code'] ?></td>
							<td>
								<p class="m-0"><b><?php echo $row['account_number'] ?></b></p>
								<p class="m-0">by: <small><b><?php echo $row['name'] ?></b></small></p>
							</td>
							<td>
								<?php 
								if($row['type'] == 1){
									echo '<span class="badge badge-primary">Deposit</span>';
								}else if($row['type'] == 2){
									echo '<span class="badge badge-success">Withdraw</span>';
								}else if($row['type'] == 3){
									echo '<span class="badge badge-warning">Transfer</span>';
								}
								?>
							</td>
							<td class="text-right"><?php echo number_format($row['amount'],2) ?></td>
							<td align="center">
								 <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
				                  		Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=transaction/view_details&id='.$row['id'] ?>" data-id ="<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> View</a>
				                  </div>
							</td>
						</tr>
					<?php endwhile; 
					} else {
						// Display a message if the query fails
						echo "<tr><td colspan='7' class='text-center'>Failed to fetch transaction data. Please check your database connection and query.</td></tr>";
					}
					?>
				</tbody>
			</table>
		</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('#transaction-list').dataTable();
	})
</script>