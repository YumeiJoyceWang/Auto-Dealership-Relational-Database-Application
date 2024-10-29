<?php
include('lib/common.php');
// written by GTusername1

if($showQueries){
  array_push($query_msg, "showQueries currently turned ON, to disable change to 'false' in lib/common.php");
}

//Note: known issue with _POST always empty using PHPStorm built-in web server: Use *AMP server instead
if( $_SERVER['REQUEST_METHOD'] == 'POST') {

	$enteredUsername = mysqli_real_escape_string($db, $_POST['username']);
	$enteredPassword = mysqli_real_escape_string($db, $_POST['password']);

    if (empty($enteredUsername)) {
            array_push($error_msg,  "Please enter an username address.");
    }

	if (empty($enteredPassword)) {
			array_push($error_msg,  "Please enter a password.");
	}
	
    if ( !empty($enteredUsername) && !empty($enteredPassword) )   { 

        $query = "SELECT password FROM loginuser WHERE username='$enteredUsername'";
        
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $count = mysqli_num_rows($result); 
        
        if (!empty($result) && ($count > 0) ) {
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $storedPassword = $row['password']; 
            
            $options = [
                'cost' => 8,
            ];
             //convert the plaintext passwords to their respective hashses
            $storedHash = password_hash($storedPassword, PASSWORD_DEFAULT , $options);
            $enteredHash = password_hash($enteredPassword, PASSWORD_DEFAULT , $options); 
            
            if($showQueries){
                array_push($query_msg, "Plaintext entered password: ". $enteredPassword);
                array_push($query_msg, "Entered Hash:". $enteredHash);
                array_push($query_msg, "Stored Hash:  ". $storedHash . NEWLINE);
            }
            
            //depends on if you are storing the hash $storedHash or plaintext $storedPassword 
            if (password_verify($enteredPassword, $storedHash) ) {
                array_push($query_msg, "Password is Valid! ");
                $_SESSION['username'] = $enteredUsername;
                array_push($query_msg, "logging in... ");
                header(REFRESH_TIME . 'url=main.php');		//to view the password hashes and login success/failure
                
            } else {
                array_push($error_msg, "Login failed: " . $enteredusername . NEWLINE);
                array_push($error_msg, "To demo enter: ". NEWLINE . "michael@bluthco.com". NEWLINE ."michael123");
            }
            
        } else {
                array_push($error_msg, "The username entered does not exist: " . $enteredUsername);
            }
    }
}
?>

<!-- <?php include("lib/header.php"); ?> -->
<title>Jaunty Jalopies Login</title>
</head>
<body>
    
    <div id="main_container">
        <div class="center_content">
            <div class="text_box">
                <p><a href='main.php'><button>Go to main page</button></a></p>
                <form action="login.php" method="post" enctype="multipart/form-data">
                    <div class="title">Jaunty Jalopies Login</div>
                    <div class="login_form_row">
                        <label class="login_label">Username:</label>
                        <input type="text" name="username" value="" class="login_input"/>
                    </div>
                    <div class="login_form_row">
                        <label class="login_label">Password:</label>
                        <input type="password" name="password" value="" class="login_input"/>
                    </div>
                    <input type="image" src="img/login.gif" class="login"/>
                </form>
            </div>

            <?php include("lib/error.php"); ?>

            <div class="clear"></div>
        </div>
    </div>
    </body>
</html>