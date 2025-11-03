<?php
require_once '../../includes/session_check_teacher.php';
require_once '../../config/pdo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $course_id = $_POST['course_id'];
  $title = $_POST['title'];
  $path = null;

  if (!empty($_FILES['file']['name'])) {
    $safe = time().'_'.preg_replace('/[^\w.-]/','_', $_FILES['file']['name']);
    $dest = __DIR__."/../../uploads/materials/".$safe;
    move_uploaded_file($_FILES['file']['tmp_name'], $dest);
    $path = "/uploads/materials/".$safe;
  }

  $stmt = $pdo->prepare("INSERT INTO materials (course_id,title,file_path,uploaded_by) VALUES (?,?,?,?)");
  $stmt->execute([$course_id,$title,$path,$_SESSION['user_id']]);
}

$courses = $pdo->prepare("SELECT id,title FROM courses WHERE teacher_id=?");
$courses->execute([$_SESSION['user_id']]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/header_admin.php'; ?>
<h2>Upload Materials</h2>

<form method="POST" enctype="multipart/form-data">
  <select name="course_id" required>
    <option value="">Select Course</option>
    <?php foreach($courses as $c): ?>
      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
    <?php endforeach; ?>
  </select>
  <input name="title" placeholder="Material title" required>
  <input type="file" name="file" required>
  <button>Upload</button>
</form>