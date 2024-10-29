<!-- get role of user -->
<?php

    include('lib/common.php');
    // get role of user
    if (!isset($_SESSION['username'])) {
        echo "<h3>Please login to look for customer</h3>";
        echo "<p><a href='login.php'><button>Login</button></a></p>";
        exit;
    }
    else{
        $query = "SELECT role, first_name, last_name FROM loginuser WHERE username = '{$_SESSION['username']}'";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $role = $row['role'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $username = $_SESSION['username'];   
    }
    
?>


<?php

include('lib/common.php');

if (!isset($_SESSION['username'])) {
	header('Location: login.php');
	exit();
}

?>

<html>
<head>
    <title>lookup_customer</title>
</head>
<body>

<h2>Search customer</h2>
    <form action="" method="post">
        Customer Info: <input type="text" name="customer_info">
        <button type='submit' value = 'submit'> Search Customer </button>
    </form>

<?php 
    // echo "{$_REQUEST['customer_info']}<br>";


if (!empty($_REQUEST['customer_info'])) {
    $driver_license = $_REQUEST['customer_info']; 
    $query = <<<EOT
            SELECT email, phone, street_address, first_name, last_name, C.customerID
            FROM Customer AS C INNER JOIN Individual AS I ON C.customerID = I.customerID
            WHERE driver_license = '{$driver_license}'
            EOT;
    
    $result = mysqli_query($db, $query);
    include('lib/show_queries.php');
    // echo 'hi';
    if (mysqli_num_rows($result) > 0) {
        echo "<h2>Customer Information</h2>";
        echo "<table>";
        echo "Found an individual customer!";
        $first_row = <<<EOT
        <tr>
            <td class="item_label">First Name</td>
            <td class="item_label">Last Name</td>
            <td class="item_label">Email</td>
            <td class="item_label">Phone</td>
            <td class="item_label">Street Address</td>
            <td class="item_label">CustomerID</td>
        </tr>
        EOT;
        echo $first_row;
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $customerID = $row['customerID'];
            $tmp_row = <<<EOT
            <tr>
                <td>{$row['first_name']}</td>
                <td>{$row['last_name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['street_address']}</td>
                <td>{$row['customerID']}</td>
            </tr>
            EOT;
            echo $tmp_row;
        }
        echo "</table>";
        return;
    }
    $taxID = $_REQUEST['customer_info']; 
    $query = <<<EOT
            SELECT email, phone, street_address, business_name, contact_first_name, contact_last_name, title, B.customerID
            FROM Customer AS C INNER JOIN Business AS B ON C. customerID = B.customerID
            WHERE taxID = '{$taxID}'
            EOT;

    $result = mysqli_query($db, $query);
    include('lib/show_queries.php');
    // echo 'hi';
    if (mysqli_num_rows($result) > 0) {
        echo "<h2>Customer Information</h2>";
        echo "<table>";
        echo "Found a business customer!";
        $first_row = <<<EOT
        <tr>
            <td class="item_label">Name</td>
            <td class="item_label">Email</td>
            <td class="item_label">Phone</td>
            <td class="item_label">Street Address</td>
            <td class="item_label">Contact First Name</td>
            <td class="item_label">Contact Last Name</td>
            <td class="item_label">Title</td>
            <td class="item_label">CustomerID</td>
        </tr>
        EOT;
        echo $first_row;
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            
            $tmp_row = <<<EOT
            <tr>
                <td>{$row['business_name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['street_address']}</td>
                <td>{$row['contact_first_name']}</td>
                <td>{$row['contact_last_name']}</td>
                <td>{$row['title']}</td>
                <td>{$row['customerID']}</td>
            </tr>
            EOT;
            echo $tmp_row;
        }
        echo "</table>";
    }
    else{
         echo 'Did not find this customer, please add!<br>';
    }
}
?>
</body>
</html>

