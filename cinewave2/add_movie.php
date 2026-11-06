<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<?php
$staff_id = $_SESSION['staff_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $movie_name = isset($_POST['movie_name']) ? trim($_POST['movie_name']) : '';
    $movie_duration = isset($_POST['movie_duration']) ? floatval($_POST['movie_duration']) : '';
    $movie_category = isset($_POST['movie_category']) ? trim($_POST['movie_category']) : '';
    $movie_genre = isset($_POST['movie_genre']) ? trim($_POST['movie_genre']) : '';
    $movie_description = isset($_POST['movie_description']) ? trim($_POST['movie_description']) : '';
    $movie_lang = isset($_POST['movie_language']) ? trim($_POST['movie_language']) : '';
    $movie_subtitle = isset($_POST['movie_subtitle']) ? trim($_POST['movie_subtitle']) : '';
    $movie_path = "";
    $movie_status = isset($_POST['movie_status']) ? trim($_POST['movie_status']) : '';

    if (
        empty($movie_name) || $movie_duration <= 0 || empty($movie_category)
        || empty($movie_genre) || empty($movie_description) || empty($movie_lang)
        || empty($movie_subtitle) || empty($movie_status)
    ) {
        echo "<script>alert('Each option must filled');window.location.href='add_movie_copy.php'</script>";
        exit();
    }

    if (!isset($_FILES['movie_img']) || $_FILES['movie_img']['error'] !== 0) {
        echo "<script>alert('Please upload proper poster!')</script>";
        exit();
    }

    $file = $_FILES['movie_img'];
    $file_ext = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));

    if($file_ext !== 'png'){
        echo "<script>alert('Only png format supported')</script>";
        exit();
    }


    try {
        $sql = "INSERT INTO movie(movie_name,synopsis,genre,duration,language,subtitle,path,current_showing_status,age_restriction,staff_id)
        VALUES(?,?,?,?,?,?,'',?,?,?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt) {
            $success = $stmt->execute([
                $movie_name,
                $movie_description,
                $movie_genre,
                $movie_duration,
                $movie_lang,
                $movie_subtitle,
                $movie_status,
                $movie_category,
                $staff_id
            ]);
            if($success){
                $new_movie_id = $pdo->lastInsertId();
                $upload_dir = 'asset/movie/';
                if(!is_dir($upload_dir)){
                    mkdir($upload_dir,0755,true);
                }

                $db_filename = $upload_dir . $new_movie_id . '.png';
                $tmp_path = $file['tmp_name'];
                
                if(move_uploaded_file($tmp_path,$db_filename)){
                    $update_sql = "UPDATE movie SET path = ? WHERE movie_id = ?";
                    $update_stmt = $pdo->prepare($update_sql);

                    if($update_stmt){
                        $update_stmt->execute([$db_filename, $new_movie_id]);
                    }

                    echo "<script>alert('Movie success added!');window.location.href = 'add_movie_copy.php'</script>";
                    exit();
                }else{
                    $pdo->query("DELETE FROM movie WHERE movie_id = $new_movie_id");
                    echo "<script>alert('Fail to save image file');window.history.back();</script>";
                    exit();
                }

            }else{
                echo "<script>alert('SQL Execution fail,please fix the bug')</script>";
                exit();
            }
        }
    } catch (Exception $e) {
        echo "<script>alert('Upload Error!');window.location.href='add_movie_copy.php';</script>";
        exit();
    }
}
?>
<html>

<head>
    <title>Cinewave_info_page</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Lavishly+Yours&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>

<body class="body">
    <main class="container">
        <form class="add_movie_form" id="movieForm" action="" method="post" enctype="multipart/form-data">
            <div class="add_movie_form_leftside">
                <h2>Add Movie:</h2>
                <div class="add_movie_form_input">
                    <label>Movie Name: </label>
                    <input type="text" name="movie_name" required>
                </div>

                <div class="add_movie_form_input">
                    <label>Movie Duration: </label>
                    <input type="number" id="movie_duration" name="movie_duration" required>
                </div>

                <div class="add_movie_form_input">
                    <label>Movie Category: </label>
                    <select name="movie_category" required>
                        <option value="">--category--</option>
                        <option value="adult" name="category">Adult</option>
                        <option value="child" name="category">Child</option>
                    </select>
                </div>

                <div class="add_movie_form_input">
                    <label>Movie Genre</label>
                    <input type="text" id="movie_genre" name="movie_genre" required>
                </div>

                <div class="add_movie_form_input">
                    <label>Language</label>
                    <input type="text" id="movie_language" name="movie_language" required>
                </div>

                <div class="add_movie_form_input">
                    <label>Subtitle</label>
                    <input type="text" id="movie_subtitle" name="movie_subtitle" required>
                </div>

                <div class="add_movie_form_input">
                    <label>Current Showing Status:</label>
                    <select name="movie_status">
                        <option value="">--status--</option>
                        <option value="Upcoming">Upcoming</option>
                        <option value="Now showing">Now Showing</option>
                        <option value="Ended">Ended</option>
                    </select>
                </div>

                <div class="add_movie_form_input">
                    <label>Movie Description</label>
                    <textarea rows="5" col="15" id="movie_description" name="movie_description" required></textarea>
                </div>

                <div class="add_movie_form_input">
                    <button type="submit" id="submit_btn" class="btn">Submit</button>
                    <button type="reset" id="resetBtn" class="btn">Clear</button>
                </div>
            </div>

            <div class="add_movie_form_rightside">
                <div class="add_movie_form_input">
                    <label id="image_label">Movie Image</label>
                    <img id="img_preview" src="" alt="image preview" style="display: none;">
                    <input type="file" id="movie_img" name="movie_img" accept="image/*" required>
                </div>
            </div>
        </form>
    </main>

    <script>
        const movieImgInput = document.getElementById('movie_img');
        const imgPreview = document.getElementById('img_preview');
        const imageLabel = document.getElementById('image_label');
        const movieForm = document.getElementById('movieForm');
        movieImgInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function(e) {
                    imgPreview.setAttribute('src', e.target.result);
                    imgPreview.style.display = 'block';
                    imageLabel.style.display = 'none';
                });
                reader.readAsDataURL(file);
            }
        });

        movieForm.addEventListener('reset', function() {
            imgPreview.setAttribute('src', '');
            imgPreview.style.display = 'none';
            imageLabel.style.display = 'block';
        });
    </script>
</body>
<?php
include "footer.php" ?>

</html>