<?php
require_once(__DIR__ . '/../../config.php');
?>
<div class="container-fluid">
<table class="table table-striped table-bordered">
    <thead class="thead-dark">
        <tr>
            <th class="text-center">Date</th>
            <th>Remarks</th>
            <th class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $account_id = isset($_GET['id']) ? $_GET['id'] : 0;
        $stmt = $conn->prepare("SELECT * FROM `transactions` WHERE account_id = ? ORDER BY date_created DESC");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0):
            while($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td class="text-center"><?php echo date("d M, Y h:i A", strtotime($row['date_created'])) ?></td>
            <td><?php echo htmlspecialchars($row['remarks']) ?></td>
            <td class="text-right">
                <?php 
                    // Type 1 is Credit, Type 2 is Debit for admin adjustments
                    if ($row['type'] == 1) { 
                        echo '<span class="text-success font-weight-bold">+ ' . number_format($row['amount'], 2) . '</span>';
                    } else { 
                        echo '<span class="text-danger font-weight-bold">- ' . number_format($row['amount'], 2) . '</span>';
                    }
                ?>
            </td>
        </tr>
        <?php 
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="3" class="text-center">No transactions found for this account.</td>
        </tr>
        <?php endif; $stmt->close(); ?>
    </tbody>
</table>
</div>
<style>
.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
</style>