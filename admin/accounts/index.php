<?php if($_settings->chk_flashdata('success')): ?>
<script>
 alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
 <div class="card-header">
  <h3 class="card-title">List of Accounts</h3>
  <div class="card-tools">
   <a href="?page=accounts/manage_account" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New</a>
  </div>
 </div>
 <div class="card-body">
  <div class="container-fluid">
   <table class="table table-bordered table-stripped" id="account-list">
    <colgroup>
     <col width="5%">
     <col width="15%">
     <col width="20%">
     <col width="20%">
     <col width="15%">
     <col width="10%">
     <col width="15%">
    </colgroup>
    <thead>
     <tr>
      <th class="text-center">#</th>
      <th>Account #</th>
      <th>Name</th>
      <th>Email</th>
      <th class="text-right">Balance</th>
      <th class="text-center">Status</th>
      <th class="text-center">Action</th>
     </tr>
    </thead>
    <tbody>
     <?php 
     $i = 1;
      // MODIFIED: Corrected column name to `date_created` and removed the filtering WHERE clause to show all accounts.
      $qry = $conn->query("SELECT *,concat(lastname,', ',firstname, ' ', middlename) as `name` from `accounts` order by `date_created` desc, `name` asc ");
      if (!$qry) {
       // This will print the SQL error for debugging if the query fails.
       die("SQL Error: " . $conn->error);
      }
      while($row = $qry->fetch_assoc()):
     ?>
      <tr>
       <td class="text-center"><?php echo $i++; ?></td>
       <td><?php echo $row['account_number'] ?></td>
       <td><?php echo $row['name'] ?></td>
       <td><?php echo $row['email'] ?></td>
       <td class='text-right'><?php echo number_format($row['balance'],2) ?></td>
       <td class="text-center">
 <?php 
  $status = strtolower($row['status']);
  switch ($status) {
   case 'active':
    echo '<span class="badge badge-success px-3 rounded-pill">Active</span>';
    break;
   case 'pending':
    echo '<span class="badge badge-warning px-3 rounded-pill">Pending</span>';
    break;
   case 'on hold':
   case 'restricted':
   case 'fixed deposit':
    echo '<span class="badge badge-danger px-3 rounded-pill">' . htmlspecialchars($row['status']) . '</span>';
    break;
   default:
    echo '<span class="badge badge-secondary px-3 rounded-pill">' . htmlspecialchars($row['status']) . '</span>';
    break;
  }
 ?>
</td>
       <td align="center">
        <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
          Action
         <span class="sr-only">Toggle Dropdown</span>
        </button>
        <div class="dropdown-menu" role="menu">
         <a class="dropdown-item" href="?page=accounts/manage_account&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit / Manage Status</a>
         <div class="dropdown-divider"></div>
         <a class="dropdown-item balance_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-money-bill text-success"></span> Deposit / Transfer</a>
         <a class="dropdown-item view_transactions" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-list text-info"></span> View Transactions</a>
         <div class="dropdown-divider"></div>
         <?php if(strtolower($row['status']) == 'pending'): ?>
          <a class="dropdown-item activate_account" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" data-name="<?php echo htmlspecialchars($row['name']) ?>"><span class="fa fa-check text-primary"></span> Activate Account</a>
          <div class="dropdown-divider"></div>
         <?php endif; ?>
         <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
        </div>
       </td>
      </tr>
     <?php endwhile; ?>
    </tbody>
    </table>
   </div>
  </div>
</div>
<script>
 function delete_account($id){
  start_loader();
  $.ajax({
   url:_base_url_+"classes/Master.php?f=delete_account",
   method:"POST",
   data:{id: $id},
   dataType:"json",
   error:err=>{
    console.log(err)
    alert_toast("An error occured.",'error');
    end_loader();
   },
   success:function(resp){
    if(typeof resp== 'object' && resp.status == 'success'){
     location.reload();
    }else{
     alert_toast("An error occured.",'error');
     end_loader();
    }
   }
  })
 }
 function activate_account($id){
  start_loader();
  $.ajax({
   url:_base_url_+"classes/Master.php?f=activate_account",
   method:"POST",
   data:{id: $id},
   dataType:"json",
   error:err=>{
    console.log(err)
    alert_toast("An error occured.",'error');
    end_loader();
   },
   success:function(resp){
    if(typeof resp== 'object' && resp.status == 'success'){
     location.reload();
    }else if(!!resp.msg){
     alert_toast(resp.msg,'error');
    }else{
     alert_toast("An error occured.",'error');
    }
    end_loader();
   }
  })
 }

 $(document).ready(function(){
  $('#account-list').dataTable({
   columnDefs: [
     { orderable: false, targets: 6 }
   ],
   order:[[0,'asc']]
  });
  $('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle');

  // MODIFIED: Changed .click() to .on() for event delegation with DataTables.
  // This ensures the click handlers work for all rows, including those added dynamically
  // after pagination, sorting, or searching.
  $(document).on('click', '.delete_data', function(){
   _conf("Are you sure to delete this Account permanently?","delete_account",[$(this).attr('data-id')])
  });

  $(document).on('click', '.activate_account', function(){
   _conf("Are you sure to activate the account of "+$(this).attr('data-name')+"?","activate_account",[$(this).attr('data-id')])
  });

  $(document).on('click', '.balance_data', function(){
   uni_modal("<i class='fa fa-hand-holding-usd'></i> Manage Account Balance","accounts/manage_balance.php?id="+$(this).attr('data-id'), "mid-large")
  });

  $(document).on('click', '.view_transactions', function(){
   uni_modal("<i class='fa fa-list'></i> Account Transactions","accounts/view_transactions.php?id="+$(this).attr('data-id'), "large")
  });
 })
</script>