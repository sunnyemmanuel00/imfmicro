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
          // Using PDO to fetch data and construct the full name in PHP
          $stmt = $conn->query("SELECT * FROM accounts ORDER BY date_created DESC, lastname ASC");
          while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
            $name = trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']);
          ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['account_number']) ?></td>
            <td><?php echo htmlspecialchars($name) ?></td>
            <td><?php echo htmlspecialchars($row['email']) ?></td>
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
                <a class="dropdown-item" href="?page=accounts/manage_account&id=<?php echo htmlspecialchars($row['id']) ?>"><span class="fa fa-edit text-primary"></span> Edit / Manage Status</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item balance_data" href="javascript:void(0)" data-id="<?php echo htmlspecialchars($row['id']) ?>"><span class="fa fa-money-bill text-success"></span> Deposit / Transfer</a>
                <a class="dropdown-item view_transactions" href="javascript:void(0)" data-id="<?php echo htmlspecialchars($row['id']) ?>"><span class="fa fa-list text-info"></span> View Transactions</a>
                <div class="dropdown-divider"></div>
                <?php if(strtolower($row['status']) == 'pending'): ?>
                  <a class="dropdown-item activate_account" href="javascript:void(0)" data-id="<?php echo htmlspecialchars($row['id']) ?>" data-name="<?php echo htmlspecialchars($name) ?>"><span class="fa fa-check text-primary"></span> Activate Account</a>
                  <div class="dropdown-divider"></div>
                <?php endif; ?>
                <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo htmlspecialchars($row['id']) ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
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
  // Rest of the script is unchanged.
</script>
