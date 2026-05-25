<?php

    function getConnection()
    {
        //change the paramters in the function below to your user id 

            $dbc = mysqli_connect('localhost', 'u202304164', 'asdASD123!', 'db202304164');            
            
            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                die('b0ther');
            }
               
        return $dbc;
    }
    
?>

