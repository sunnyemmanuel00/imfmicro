<h1 class="text-dark main-dashboard-title">Welcome to <?php echo $_settings->info('name') ?></h1>
<?php 
// This will display your login success message, just as before.
if(isset($_SESSION['login_success_message'])): 
?>
<script>
  // Use toastr to show a success message
  toastr.success('<?php echo addslashes($_SESSION['login_success_message']); ?>', 'Login Successful');
</script>
<?php 
  // Unset the session variable so it only shows once
  unset($_SESSION['login_success_message']); 
endif; 
?>
<hr>
<div class="row">

  <div class="col-lg-5">

    <div class="card card-outline card-primary mb-4">
      <div class="card-header">
        <h3 class="card-title">Account Overview</h3>
      </div>
      <div class="card-body">
        <h3 class="account-details-text">Account Number: <?php echo $_settings->userdata('account_number') ?></h3>
        <h3 class="account-details-text">Current Balance: <?php echo '$USD' . number_format($_settings->userdata('balance'), 2, '.', ','); ?></h3>
        <?php
$status_text = $_settings->userdata('status');
  $status_color_class = ''; 

  // This new logic checks for all your custom statuses
  switch (strtolower($status_text)) {
    case 'active':
      $status_color_class = 'status-active-text-on-blue'; // Greenish
      break;
    case 'pending':
      $status_color_class = 'status-unknown-text-on-blue'; // Greyish for Pending
      break;
    case 'on hold':
    case 'restricted':
    case 'fixed deposit':
      $status_color_class = 'status-restricted-text-on-blue'; // Reddish for these statuses
      break;
    default:
      // This will catch any other status and display it with a neutral color
      $status_color_class = 'status-unknown-text-on-blue';
      break;
  }
        ?>
        <h3 class="account-details-text status-display-box">Account Status: <span class="<?php echo $status_color_class; ?> font-weight-bold"><?php echo $status_text; ?></span></h3>
      </div>
    </div> <div class="card card-outline card-info">
      <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-around quick-actions-container">
          <a href="./?page=transaction/deposit" class="btn btn-primary m-2 flex-fill quick-action-btn">Deposit</a>
          <a href="./?page=transaction/transfer" class="btn btn-success m-2 flex-fill quick-action-btn">Transfer</a>
          <a href="./?page=transaction" class="btn btn-secondary m-2 flex-fill quick-action-btn">View Transactions</a>
        </div>
      </div>
    </div> </div> <div class="col-lg-7">

    <div class="card card-outline card-success h-100">
      <div class="card-header">
        <h3 class="card-title">Recent Transactions</h3>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
          <thead class="thead-dark">
            <tr>
              <th class="text-center" style="width: 25%;">Date</th>
              <th>Description</th>
              <th class="text-right" style="width: 30%;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            global $conn;
            $account_id = $_settings->userdata('account_id');

                        // Use PDO prepare statement for PostgreSQL
                        // Corrected the SQL query to use double quotes for the table name
                        $stmt = $conn->prepare('SELECT * FROM "transactions" WHERE account_id = ? ORDER BY date_created DESC LIMIT 5');

                        // Execute the statement with the parameter directly in the execute method
                        $stmt->execute([$account_id]);

            if($stmt->rowCount() > 0):
              while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
            ?>
            <tr>
              <td class="text-center"><?php echo date("d M, Y", strtotime($row['date_created'])) ?></td>
              <td>
                <?php echo htmlspecialchars($row['remarks']); ?>
              </td>
              <td class="text-right">
               <?php 
  // Based on the 'type' column: 1 = Credit, 2 or 3 = Debit
  if ($row['type'] == 1) { 
    echo '<span class="text-success font-weight-bold">+ $' . number_format($row['amount'], 2) . '</span>';
  } else { 
    echo '<span class="text-danger font-weight-bold">- $' . number_format($row['amount'], 2) . '</span>';
  }
?>
              </td>
            </tr>
            <?php 
              endwhile;
            else:
            ?>
            <tr>
              <td colspan="3" class="text-center">No recent transactions to display.</td>
            </tr>
            <?php endif; $stmt->closeCursor(); ?>
          </tbody>
        </table>
      </div>
    </div>

  </div> </div> 

<style>
/* Styles for the main dashboard title */
.main-dashboard-title {
  font-size: 1.8rem; /* REDUCED from 2.5rem - Smaller font size for larger screens (e.g., desktops) */
  text-align: center; /* Center align the text if desired */
  margin-bottom: 1rem; /* Add some space below the title */
}

/* Styles for Account Number and Current Balance */
.account-details-text {
  font-size: 1.2rem; /* REDUCED from 1.8rem - Smaller font size for desktop */
  margin-bottom: 0.5rem; /* Space between the two lines */
}

/* UPDATED STYLES FOR ACCOUNT STATUS */
.status-display-box {
  background-color: #004085; /* Blue background */
  color: #ffffff; /* White text for "Account Status:" label */
  padding: 8px 12px; /* Slightly REDUCED padding */
  border-radius: 6px; /* Slightly smaller rounded corners */
  margin-top: 10px; /* Slightly REDUCED space above the status box */
  display: inline-block; /* Makes the background fit the content */
  width: auto; /* Ensure it wraps content */
  box-shadow: 0 1px 3px rgba(0,0,0,0.2); /* Subtle shadow for depth */
  font-size: 1.1rem; /* REDUCED - Adjusted to fit better with account-details-text */
}

/* Custom colors for the status text when on a blue background */
.status-active-text-on-blue {
  color: #98fb98; /* Brighter, light green for "Active" */
}

.status-restricted-text-on-blue {
  color: #ff9999; /* Brighter, light red for "Restricted" */
}

.status-unknown-text-on-blue {
  color: #cccccc; /* Lighter grey for "Unknown" */
}
/* END UPDATED STYLES FOR ACCOUNT STATUS */

/* Styles for Quick Actions heading */
.quick-actions-title {
  font-size: 1.3rem; /* Adjusted for better hierarchy */
  margin-bottom: 1rem; /* Space below heading */
  text-align: center; /* ADDED: Center align the Quick Actions text */
}

/* Styles for Quick Actions buttons */
.quick-actions-container {
  gap: 8px; /* Slightly REDUCED space between buttons */
}
.quick-action-btn {
  min-width: 140px; /* Slightly REDUCED minimum width */
  max-width: 180px; /* Slightly REDUCED maximum width */
  font-size: 1rem; /* REDUCED from 1.1rem */
  padding: 0.6rem 0.9rem; /* Slightly REDUCED padding */
  border-radius: 0.4rem; /* Slightly smaller rounded corners */
  text-align: center; /* Center text within buttons */
}


/* Media query for medium screens (e.g., tablets, typically up to 768px) */
@media (max-width: 768px) {
  .main-dashboard-title {
    font-size: 1.5rem; /* Reduce font size for tablets */
  }
  .account-details-text {
    font-size: 1.1rem; /* Reduce font size for tablets */
  }
  .quick-actions-title {
    font-size: 1.2rem;
  }
  .status-display-box {
    font-size: 1.05rem; /* Adjust font size for tablets */
  }
  .quick-action-btn {
    font-size: 0.95rem;
    min-width: unset; /* Allow buttons to shrink on small screens */
    width: 48%; /* Adjust width for 2 columns */
  }
}

/* Media query for small screens (e.g., mobile phones, typically up to 576px) */
@media (max-width: 576px) {
  .main-dashboard-title {
    font-size: 1.2rem; /* Further reduce font size for mobile phones */
  }
  .account-details-text {
    font-size: 0.9rem; /* Further reduce font size for mobile phones */
  }
  .quick-actions-title {
    font-size: 1.1rem;
  }
 .status-display-box {
    font-size: 0.9rem; /* Adjust font size for mobile phones */
    display: block; /* Make it full width on mobile */
    text-align: center; /* Center text within the box */
    padding-bottom: 12px; /* Added: give a little more space at the bottom */
  }
  /* This new rule forces the status text to a new line */
  .status-display-box span {
    display: block;
    margin-top: 4px; /* Adds a small space between the label and the status */
  }
  .quick-action-btn {
    width: 100%; /* Make buttons full width on very small screens */
    margin: 5px 0; /* Adjust margin for stacking */
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
  }
}
</style>
