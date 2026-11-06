<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

if($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST['action']) && $_POST['action'] == 'update'){
    $movie_id = intval($_POST['movie_id']);
    $movie_name = trim($_POST['movie_name']);
    $synopsis = trim($_POST['synopsis']);
    $genre = trim($_POST['genre']);
    $duration = floatval($_POST['duration']);
    $language = trim($_POST['language']);
    $subtitle = trim($_POST['subtitle']);
    $status = trim($_POST['current_showing_status']);
    $age_restriction = trim($_POST['age_restriction']);
    $staff_id = intval($_SESSION['staff_id']);

    $update_sql = "UPDATE movie SET movie_name = ?,synopsis=?,genre=?,
    duration = ?,language=?,subtitle = ?,current_showing_status = ?,age_restriction=?,
    staff_id=? WHERE movie_id = ?";
    $stmt = $pdo->prepare($update_sql);
if ($stmt) {
    // 1. Pass parameters as an array directly into execute()
    $success = $stmt->execute([
        $movie_name,
        $synopsis,
        $genre,
        $duration,
        $language,
        $subtitle,
        $status,
        $age_restriction,
        $staff_id,
        $movie_id
    ]);

    if ($success) {
        echo "<script>alert('Movie update successfully!'); window.location.href=window.location.href;</script>";
    } else {
        // 2. Fetch error details using errorInfo() in PDO
        $error = $stmt->errorInfo()[2];
        echo "<script>alert('Update fail: " . addslashes($error) . "'); window.location.href=window.location.href;</script>";
    }
    // No $stmt->close() needed in PDO
}
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete'){
    $movie_id = intval($_POST['movie_id']);
    $resipotory = $pdo->query("SELECT path FROM movie WHERE movie_id = $movie_id");

    $sql_delete = "DELETE FROM movie WHERE movie_id = ?";
    $stmt = $pdo->prepare($sql_delete);
    if($stmt){
    if ($stmt) {
    $stmt->execute([
        ':movie_id' => $movie_id
    ]);

    echo "<script>alert('Movie Delete Successfully'); window.location.href = window.location.href;</script>";
}
    }
}
?>

<?php
$sql = "SELECT * FROM movie";
$result = $pdo->query($sql);
include "header.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cinewave_info_page</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        .movie_info_edit_form{
            display:flex;
            padding:0.5rem;
            gap:0.5rem;
            align-items: center;
        }

        .management-table th,.management-table td{
            border:1px solid #ccc;
            padding: 0.5rem;
            text-align:left;
        }

        .management-table input,.management-table select{
            width:100%;
            box-sizing:border-box;
        }
        
    </style>

</head>

<body class="body">
    <main class="container">
        
        <div class="movie-cardlist">
            <h2>Cinewave movie info edit</h2>
            <table class="management-table">
                <thead>
                    <th>Movie ID</th>
                    <th>Movie Name</th>
                    <th>Synopsis</th>
                    <th>Genre</th>
                    <th>Duration</th>
                    <th>Language</th>
                    <th>Subtitle</th>
                    <th>Showing Status</th>
                    <th>Age Restriction</th>
                    <th>Staff ID</th>
                    <th>Operation</th>
                </thead>
                <tbody>
                <?php
                if($result&&$result->rowCount() > 0 && $result){
                    while($movie = $result -> fetch(PDO::FETCH_ASSOC)){
                        $form_id = "form_".$movie['movie_id'];
                        $image_src = !empty($movie['path']) ? str_replace('asset/movie','../movie/',$movie['path']) : '../movie.default.png';
                        $selected_now = ($movie['current_showing_status']=='Now Showing')?"selected":"";
                        $selected_soon = ($movie['current_showing_status']=='Coming Soon')?"selected":"";
                        $selected_upcome = ($movie['current_showing_status']=='Upcoming')?"selected":"";
                        $current_age = strtoupper($movie['age_restriction']);
                        $age_U = ($current_age == 'U') ? "selected" : "";
                        $age_pg13 = ($current_age == 'PG13') ? "selected" : "";
                        $age_adult = ($current_age == 'ADULT') ? "selected" : "";
                        
                        echo'<tr>';
                        echo'<td>'.$movie['movie_id'].'<input type="hidden" name="movie_id" form="' .$form_id. '" value="' .$movie['movie_id']. '" required></td>';
                        echo '<td><input type="text" name="movie_name" form="' .$form_id.'" value="' .htmlspecialchars($movie['movie_name']). '" required></td>';
                        echo '<td><input type="text" name="synopsis" form="' .$form_id.'" value="' .htmlspecialchars($movie['synopsis']). '"required></td>';
                        echo '<td><input type="text" name="genre" form="' .$form_id.'" value="' .htmlspecialchars($movie['genre']). '"required></td>';
                        echo '<td><input type="number" step="0.1" name="duration" form="' .$form_id.'" value="' .$movie['duration']. '"required></td>';
                        echo '<td><input type="text" name="language" form="' .$form_id.'" value="' .htmlspecialchars($movie['language']). '"required></td>';
                        echo '<td><input type="text" name="subtitle" form="' .$form_id.'" value="' .htmlspecialchars($movie['subtitle']). '"required></td>';
                        
                        echo'<td>';
                        echo'<select name="current_showing_status" form="' .$form_id. '" required>';
                        echo '<option value="Now Showing" ' .$selected_now. '>Now Showing</option>';
                        echo '<option value="Coming Soon" ' .$selected_soon. '>Coming Soon</option>';
                        echo '<option value="Upcoming" ' .$selected_upcome. '>Upcoming</option>';          
                        echo'</select>';
                        echo'</td>';

                        echo'<td>';
                        echo'<select name="age_restriction" form="' .$form_id. '" required>';
                        echo'<option value="U" ' .$age_U.'>U</option>';
                        echo'<option value="PG13" ' .$age_pg13.'>PG13</option>';
                        echo'<option value="Adult" ' .$age_adult.'>Adult</option>';
                        echo'</select>';
                        echo'</td>';
                        
                        echo '<td><input type="number" name="staff_id" form="' .$form_id. '" value="' .$movie['staff_id']. '" required></td>';
                        
                        echo '<td>';
                        echo '<form id="' . $form_id . '" method="POST" action=""></form>';
                        echo '<button type="submit" name="action" value="update" form="' . $form_id . '" class="btn" onclick="return confirm(\'Are you sure to update ' . addslashes($movie['movie_name']) . '?\')">Save</button> ';
                        echo '<button type="submit" name="action" value="delete" form="' . $form_id . '" class="btn" onclick="return confirm(\'Are you sure to delete ' . addslashes($movie['movie_name']) . '?\')">Delete</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
            ?>
                </tbody>
            </table>

        </div>
    </main>
</body>
<?php
include "footer.php" ?>

</html>