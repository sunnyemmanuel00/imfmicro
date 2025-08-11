<?php 
// Check if the flashdata success message exists and display an alert
if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<style>
    /* Custom styles for dashboard info cards */
    #dashboard-widgets .info-box{
        box-shadow: 0 0 1px rgb(0 0 0 / 13%), 0 1px 3px rgb(0 0 0 / 20%);
        border-radius: 10px;
    }
</style>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Welcome to - IMF Micro Finance Bank</h1>
            </div>
        </div>
    </div>
</div>
<section class="content" id="dashboard-widgets">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Accounts</span>
                        <span class="info-box-number">
                            <?php 
                            // Use PDO to fetch the count of accounts
                            try {
                                // Corrected query for PostgreSQL
                                $result = $conn->query("SELECT COUNT(id) FROM \"accounts\"");
                                // Fetch the first column of the first row
                                $row = $result->fetch(PDO::FETCH_NUM);
                                echo htmlspecialchars($row[0]);
                            } catch (PDOException $e) {
                                // Display a user-friendly error message
                                echo "Error fetching data: " . htmlspecialchars($e->getMessage());
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Balance</span>
                        <span class="info-box-number">
                            <?php 
                            // Use PDO to fetch the sum of all account balances
                            try {
                                // Corrected query for PostgreSQL
                                $result = $conn->query("SELECT SUM(balance) FROM \"accounts\"");
                                $row = $result->fetch(PDO::FETCH_NUM);
                                echo number_format(htmlspecialchars($row[0]), 2);
                            } catch (PDOException $e) {
                                echo "Error fetching data: " . htmlspecialchars($e->getMessage());
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-th-list"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Transactions</span>
                        <span class="info-box-number">
                            <?php 
                            // Use PDO to count the number of transactions
                            try {
                                // Corrected query for PostgreSQL
                                $result = $conn->query("SELECT COUNT(id) FROM \"transactions\"");
                                $row = $result->fetch(PDO::FETCH_NUM);
                                echo htmlspecialchars($row[0]);
                            } catch (PDOException $e) {
                                echo "Error fetching data: " . htmlspecialchars($e->getMessage());
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
