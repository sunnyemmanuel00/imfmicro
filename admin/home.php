<?php
// Note: This assumes $conn is a valid PDO object.
?>
<h1 class="text-dark">Welcome to <?php echo $_settings->info('name') ?></h1>
<?php
// Initialize variables to 0
$total_accounts = 0;
$total_balance = 0;

// Get total number of accounts
try {
    // Using PDO to count the number of rows
    $query_accounts = $conn->query("SELECT count(id) FROM accounts");
    $total_accounts = $query_accounts->fetchColumn(); // Fetches a single column from the next row of a result set
} catch (PDOException $e) {
    // Handle query error, though in most cases a count will succeed
    error_log("Failed to count accounts: " . $e->getMessage());
}

// Get total accounts balance
try {
    // Using PDO to get the sum of all balances
    $query_balance = $conn->query("SELECT sum(balance) as total FROM accounts");
    $result_balance = $query_balance->fetch(PDO::FETCH_ASSOC);

    // Safely check if the value exists and is not null before formatting
    if ($result_balance && isset($result_balance['total'])) {
        $total_balance = $result_balance['total'];
    }
} catch (PDOException $e) {
    // Handle query error
    error_log("Failed to get total balance: " . $e->getMessage());
}
?>
<hr>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-id-card"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Accounts</span>
                    <span class="info-box-box">
                        <?php echo number_format($total_accounts); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-money-bill"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Accounts Balance</span>
                    <span class="info-box-number">
                        <?php echo number_format($total_balance, 2); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
