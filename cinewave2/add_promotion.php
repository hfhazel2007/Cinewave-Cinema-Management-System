<?php 
 require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
?>

<!DOCTYPE html>
<html>
    <head>
        <title> CineWave- Add Promotion</title>
    </head>
    <body>
        <?php
        if (isset($_POST["submit"])) {
            $promotion_name=$_POST['promotion_name'];
            $discount_percentage=$_POST['discount_percentage'];
            $conditions=$_POST['conditions'];
            

        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
            $file= $_FILES['image'];
            $filename= $file['name'];
            $filetemp= $file['tmp_name'];
            $fileError= $file['error'];
            $fileExt =strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            if($fileExt=='png'){
                    $target="asset/promotion/";
                    $relative_path= $target.$filename;
                    
                if(move_uploaded_file($filetemp,$relative_path)){
                    $path=$relative_path;
                   
                }else {
                echo "<p style='color:red;'>Error: Failed to move uploaded file. Check directory write permissions.</p>";
            }
        }}
        $sql = "INSERT INTO promotion (promotion_name, discount_percentage, conditions, path) 
                VALUES ('$promotion_name', '$discount_percentage', '$conditions', '$path')";
        
        if ($pdo->query($sql) === TRUE) {
            header("Location: promotion_management.php");
            exit();
        } else {
            echo "<p style='color:red;'>Database Error: " . $pdo->$error . "</p>";
        }
    }

        ?>
    <form action="add_promotion.php" method="post" enctype="multipart/form-data">
     Promotion Name:<br>
    <input type="text" name="promotion_name"  required>
    <br><br>

    Discount Percentage:<br>
    <input type="text" name="discount_percentage" required>
     <br><br>

    Photo:<br>
    <input type="file" name="image" id="imageSelect" accept=".png" required>
    <br><br>
    

    Conditions<br>
    <input type="text" name="conditions"  required>
     <br><br>

    <input type="submit" name="submit" value="Submit">
</form>
    </body>
</html>