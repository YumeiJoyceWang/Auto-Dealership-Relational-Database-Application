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
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $username = $_SESSION['username'];
        echo "<h3>Welcome, {$first_name} {$last_name}, [role: {$role}, username: {$username}]</h3>";   
    }
    
?>

<!-- php script to add customer to database when the form is submitted-->

<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        $email = $_POST['email'];

        $phone = $_POST['phone'];

        $street_address = $_POST['street_address'];

        $city = $_POST['city'];

        $state = $_POST['state'];

        $postal_code = $_POST['postal_code'];

        $driver_license = $_POST['driver_license'];
        
        $query = <<<EOT
        SELECT email, phone, street_address, first_name, last_name, C.customerID
        FROM Customer AS C INNER JOIN Individual AS I ON C.customerID = I.customerID
        WHERE driver_license = '{$driver_license}'
        EOT;
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) > 0){
            echo 'Driver License already exists';
            exit;
        }
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];

        $business_name = $_POST['business_name'];
        $contact_first_name = $_POST['contact_first_name'];
        $contact_last_name = $_POST['contact_last_name'];
        $title = $_POST['title'];
        $taxID = $_POST['taxID'];
        
        $query = <<<EOT
        SELECT email, phone, street_address, business_name, contact_first_name, contact_last_name, title, B.customerID
        FROM Customer AS C INNER JOIN Business AS B ON C. customerID = B.customerID
        WHERE taxID = '{$taxID}'
        EOT;

        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) > 0){
            echo 'TaxID already exists';
            exit;
        }

        if (empty($phone) || empty($street_address) || empty($city) || empty($state) || empty($postal_code)){
            echo "Hello, Please fill the form. Only email can be empty.";
        }
        elseif ($_POST['customer_type'] == 'individual' && (empty($driver_license) || empty($first_name) || empty($last_name))) {
            echo "Indi, Please fill the form. Only email can be empty.";
            exit;
        }
        elseif ($_POST['customer_type'] == 'business' && (empty($business_name) || empty($contact_first_name) || empty($contact_last_name) || empty($title)|| empty($taxID))){
            echo "Busi, Please fill the form. Only email can be empty.";
            exit;
        }
        else{
            if (empty($email)){
                $query = <<<EOT
                INSERT INTO customer (email, phone, street_address, city, state, postal_code) VALUES (NULL, '{$phone}', '{$street_address}', '{$city}', '{$state}', '{$postal_code}');
                EOT;
                
            }
            else{
                $query = <<<EOT
                INSERT INTO customer (email, phone, street_address, city, state, postal_code) VALUES ('{$email}', '{$phone}', '{$street_address}', '{$city}', '{$state}', '{$postal_code}');
                EOT;
            }
            // echo $query;
            echo "You have inserted one". ' ' . $_POST['customer_type']. ' '. 'customer';
            $result = mysqli_query($db, $query);
        }

        if ($_POST['customer_type'] == 'individual'){
            $query_add_customer = "INSERT INTO individual (driver_license, first_name, last_name, customerID) VALUES ('{$driver_license}', '{$first_name}', '{$last_name}', (SELECT max(customerID) FROM customer))";
            //add_customer
            // echo $query_add_customer;
        }
        else{
            $query_add_customer = "INSERT INTO business (taxID, business_name, title, contact_first_name, contact_last_name,  customerID) VALUES ('{$taxID}', '{$business_name}', '{$title}', '{$contact_first_name}', '{$contact_last_name}', (SELECT max(customerID) FROM customer))";
            // echo $query_add_customer;
        }
    
        #add vehicle attribute
        $result = mysqli_query($db, $query_add_customer);
        // to another page

    }

?>

<html>
<head>
    <title>add customer</title>
</head>
<body>
    <p><a href='main.php'><button>Go to main page</button></a></p>
    <h2>Please fill the form to add a customer.</h2>
    <!-- add customer form -->
    <form name="addCustomer" action="add_customer.php" method="post">
        <label for="customer_type">Choose a customer type:</label>

        <select name="customer_type" id="ct" onchange = "setForm(this.value)">
            <option value="individual">Individual</option>
            <option value="business">Business</option>
        </select>
        <br>
        <div class="individual" id="individual">
            <label for="first_name">First name*:</label>
            <input type="text" id="first_name" name="first_name"><br><br>
            <label for="last_name">Last name*:</label>
            <input type="text" id="last_name" name="last_name"><br><br>
            <label for="driver_license">Driver License*:</label>
            <input type="text" id="driver_license" name="driver_license"><br><br>
        </div>
        <div class="business" id="business" style="display: none">
            <label for="business_name">Business name*:</label>
            <input type="text" id="business_name" name="business_name"><br><br>
            <label for="contact_first_name">Contact First Name*:</label>
            <input type="text" id="contact_first_name" name="contact_first_name"><br><br>
            <label for="contact_last_name">Contact Last Name*:</label>
            <input type="text" id="contact_last_name" name="contact_last_name"><br><br>
            <label for="title">Title*:</label>
            <input type="text" id="title" name="title"><br><br>
            <label for="taxID">tax ID*:</label>
            <input type="text" id="taxID" name="taxID"><br><br>
        </div>

        <div  id="general">
        <label for="phone">Phone*:</label>
        <input type="tel" id="phone" maxlength='10' name="phone"><br><br>
        <label for="email">Email: </label>
        <input type="email" id="email" name="email"><br><br>
        <label for="street_address">Street Address*:</label>
        <input type="text" id="street_address" name="street_address"><br><br>
        <label for="city">City*: </label>
        <input type="text" id="city" name="city"><br><br>
        <label for="state">State*:</label>
        <input type="text" id="state" name="state"><br><br>
        <label for="postal_code">Postal code*:</label>
        <input type="number" id="postal_code" name="postal_code"><br><br>
        </div>

    <script type="text/javascript">
        function setForm(value) {

            if(value == 'individual'){
                        document.getElementById('individual').style='display:block;';
                        
                        document.getElementById('business').style='display:none;';
                    }
            else {

                document.getElementById('business').style = 'display:block;';
                document.getElementById('individual').style = 'display:none;';
            }
            }
    </script>

    <a href='javascript:addCustomer.submit();'> <button>Add Customer</button></a>
    </form>

</body>

</html>
