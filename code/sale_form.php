<!-- get role of user -->
<?php

include('lib/common.php');
    // get role of user
    if (!isset($_SESSION['username'])) {
        echo "<h3>Please login to add a vehicle</h3>";
        echo "<p><a href='login.php'><button>Login</button></a></p>";
        exit;
    }
    else{
        $query = "SELECT role, first_name, last_name FROM loginuser WHERE username = '{$_SESSION['username']}'";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $role = $row['role'];
        if (!str_contains($role, 'sales_person')) {
            echo 'You are not authorized to sell this car!';
            exit;
        }
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $username = $_SESSION['username'];
    }

    // add a sale to database
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $username = $_SESSION['username'];
        // echo $username;
        $VIN = $_GET['VIN'];
        // echo $_GET['VIN'];
        $customerID = $_POST['customerID'];
        // check if customer is in db.
        $query1 = <<<EOT
        SELECT customerID        
        FROM customer
        WHERE customerID = '{$customerID}';
        EOT;
        $result1 = mysqli_query($db, $query1);

        $sold_date = $_POST['sold_date'];
    
        $sold_price = $_POST['sold_price'];

        $query = <<<EOT
        SELECT 1.25 * invoice_price as list_price, is_sold            
        FROM vehicle
        WHERE VIN = '{$VIN}';
        EOT;
        $result = mysqli_query($db, $query);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

        if (empty($VIN) || empty($customerID) || empty($sold_date) || empty($sold_price)){
            
        }
        elseif ($sold_price < 0.95 * $row['list_price'] && !str_contains($role, 'owner')) {
            echo "The sold price is invalid!";
        }
        elseif ($row['is_sold'] == TRUE){
            echo "The vehicle has been sold!";
        }
        elseif(mysqli_num_rows($result1) == 0){
        echo 'Cannot find this customer, please make sure the info is correct!';
        }
        else{
            $query = <<<EOT
            INSERT INTO sale (username, customerID, VIN, sold_date, sold_price) VALUES ('{$username}','{$customerID}', '{$VIN}',  '{$sold_date}', '{$sold_price}');
            EOT;
            $result = mysqli_query($db, $query);
            // echo $query;
            $query = <<<EOT
            UPDATE vehicle
            SET is_sold = '1'
            WHERE VIN = '{$VIN}';
            EOT;
            $result = mysqli_query($db, $query);
            echo "Congratulations! You sold a car!";
            header("refresh:2; url=main.php");
            exit();
            
        }
        

    }

   
    
?>


<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_info = $_POST['customer_info'];
    }
?>

<html>
<head>
    <title>Sale Order Form</title>
</head>

<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<?php include('lookup_customer.php');
?>

<a href='add_customer.php'> <button>Add Customer</button></a>
<br>
<hr>

<h2>Add sale</h2>

<form name="addSale" action="" method="post" action="javascript:checkDOB()">
    <div class="individual" id="individual">
        <label for="sold_date">Sale Date*:</label>
        <input type="date" id="sold_date" name="sold_date" max="<?= date('Y-m-d'); ?>" required><br><br>
        <label for="sold_price">Sale Price*:</label>
        <input type="number" step="0.01" id="sold_price" name="sold_price" required><br><br>
        <label for="customerID">CustomerID*:</label>
        <input type="text" id="customerID" name="customerID" required><br><br>
    </div>
    <script type="text/javascript">
    function checkDOB() {
        var dateString = document.getElementById('id_dateOfBirth').value;
        var myDate = new Date(dateString);
        var today = new Date();
        if ( myDate > today ) { 
            $('#id_dateOfBirth').after('<p>You cannot enter a date in the future!.</p>');
            return false;
        }
        return true;
    }
    </script>

<a href='javascript:sale_form.submit();'> <button>Save Sale</button></a>
</form>

</body>
</html>