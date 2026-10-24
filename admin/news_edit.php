<?php

require_once __DIR__ . "/require-admin.php";

require_once "../config/database.php";

if (!isset($_SESSION["inline_image_csrf"])) {
    $_SESSION["inline_image_csrf"] = bin2hex(random_bytes(32));
}

// ตรวจสอบ ID ข่าว
$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id) {
    die("ไม่พบข่าวที่ต้องการแก้ไข");
}


// ดึงข้อมูลข่าว
$stmt = $pdo->prepare("
    SELECT *
    FROM news
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ":id" => $id
]);

$news = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news) {
    die("ไม่พบข่าวนี้");
}


// ดึงหมวดหมู่
$stmt = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


$error = "";


// เมื่อกดบันทึก
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $content = trim($_POST["content"] ?? "");
    $source = trim($_POST["source"] ?? "");
    $status = $_POST["status"] ?? "draft";

    $imageName = $news["image"];


    // ตรวจสอบข้อมูล
    if (
        $title === "" ||
        $category_id === "" ||
        $content === ""
    ) {

        $error = "กรุณากรอกข้อมูลที่จำเป็นให้ครบ";

    } else {


        // ถ้ามีการเลือกรูปใหม่
        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {

            $file = $_FILES["image"];


            // จำกัดขนาด 5 MB
            if ($file["size"] > 5 * 1024 * 1024) {

                $error = "รูปภาพต้องมีขนาดไม่เกิน 5 MB";

            } else {

                $allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];


                $finfo = finfo_open(
                    FILEINFO_MIME_TYPE
                );

                $mimeType = finfo_file(
                    $finfo,
                    $file["tmp_name"]
                );

                finfo_close($finfo);


                if (
                    !in_array(
                        $mimeType,
                        $allowedTypes
                    )
                ) {

                    $error =
                        "อนุญาตเฉพาะ JPG, PNG และ WEBP";

                } else {


                    $extension = match ($mimeType) {

                        "image/jpeg" => "jpg",

                        "image/png" => "png",

                        "image/webp" => "webp"

                    };


                    $newImageName =
                        bin2hex(
                            random_bytes(16)
                        )
                        . "."
                        . $extension;


                    $uploadDir =
                        "../assets/images/";


                    $uploadPath =
                        $uploadDir
                        . $newImageName;


                    if (
                        move_uploaded_file(
                            $file["tmp_name"],
                            $uploadPath
                        )
                    ) {

                        // ลบรูปเก่า
                        if (
                            !empty($news["image"])
                        ) {

                            $oldImage =
                                $uploadDir
                                . $news["image"];


                            if (
                                file_exists(
                                    $oldImage
                                )
                            ) {

                                unlink(
                                    $oldImage
                                );
                            }
                        }


                        $imageName =
                            $newImageName;

                    } else {

                        $error =
                            "อัปโหลดรูปภาพไม่สำเร็จ";

                    }
                }
            }
        }


        // อัปเดตข่าว
        if ($error === "") {

            $sql = "
                UPDATE news

                SET
                    category_id = :category_id,
                    title = :title,
                    content = :content,
                    image = :image,
                    source = :source,
                    status = :status

                WHERE id = :id
            ";


            $stmt = $pdo->prepare($sql);


            $stmt->execute([

                ":category_id" => $category_id,

                ":title" => $title,

                ":content" => $content,

                ":image" => $imageName,

                ":source" => $source,

                ":status" => $status,

                ":id" => $id

            ]);


            header(
                "Location: news.php?updated=1"
            );

            exit;
        }
    }
}

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        แก้ไขข่าว - ข่าวสารภายในประเทศ CMS
    </title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            color: #222;
        }


        .header {
            background: #173b6c;
            color: white;

            padding: 20px 40px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }


        .logo {
            font-size: 24px;
            font-weight: bold;
        }


        .back {
            color: white;
            text-decoration: none;

            background: #295d9b;

            padding: 10px 16px;

            border-radius: 8px;
        }


        .container {
            max-width: 900px;

            margin: auto;

            padding: 40px 20px;
        }


        .box {
            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.05);
        }


        h1 {
            margin-bottom: 30px;
        }


        .form-group {
            margin-bottom: 20px;
        }


        label {
            display: block;

            margin-bottom: 8px;

            font-weight: bold;
        }


        input,
        select,
        textarea {
            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            font-size: 15px;
        }


        textarea {
            min-height: 250px;

            resize: vertical;
        }


        input:focus,
        select:focus,
        textarea:focus {
            outline: none;

            border-color: #173b6c;
        }


        .error {
            background: #ffe5e5;

            color: #c62828;

            padding: 12px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        .button {
            border: none;

            background: #173b6c;

            color: white;

            padding: 13px 25px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 16px;
        }


        .button:hover {
            background: #295d9b;
        }


        .current-image {
            width: 250px;

            max-height: 180px;

            object-fit: cover;

            border-radius: 10px;

            margin-bottom: 10px;

            display: block;
        }


        .required {
            color: red;
        }


        @media (max-width: 768px) {

            .header {
                padding: 20px;
            }

            .box {
                padding: 20px;
            }

        }

    </style>
    <link rel="stylesheet" href="admin-transition.css?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.css"); ?>">
    <link rel="stylesheet" href="admin-inline-images.css?v=<?php echo (int) filemtime(__DIR__ . "/admin-inline-images.css"); ?>">

</head>


<body>


<header class="header">

    <div class="logo">
        WORLD NEWS CMS
    </div>


    <a
        href="news.php"
        class="back"
    >
        ← จัดการข่าว
    </a>

</header>


<main class="container">


    <div class="box">

        <h1>
            ✏️ แก้ไขข่าว
        </h1>


        <?php if ($error !== ""): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >
            <input type="hidden" name="inline_image_csrf" value="<?php echo htmlspecialchars($_SESSION["inline_image_csrf"], ENT_QUOTES, "UTF-8"); ?>">


            <!-- หัวข้อ -->

            <div class="form-group">

                <label>
                    หัวข้อข่าว
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="title"
                    value="<?php
                    echo htmlspecialchars(
                        $news["title"]
                    );
                    ?>"
                    required
                >

            </div>


            <!-- หมวดหมู่ -->

            <div class="form-group">

                <label>
                    หมวดหมู่
                    <span class="required">*</span>
                </label>


                <select
                    name="category_id"
                    required
                >

                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?php
                            echo $category["id"];
                            ?>"
                            <?php
                            if (
                                $category["id"]
                                == $news["category_id"]
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $category["name"]
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- เนื้อหา -->

            <div class="form-group">

                <label>
                    เนื้อหาข่าว
                    <span class="required">*</span>
                </label>


                <textarea
                    name="content"
                    required
                ><?php
                echo htmlspecialchars(
                    $news["content"]
                );
                ?></textarea>

            </div>


            <!-- รูปภาพเดิม -->

            <div class="form-group">

                <label>
                    รูปภาพปัจจุบัน
                </label>


                <?php if (
                    !empty($news["image"])
                ): ?>

                    <img
                        src="../assets/images/<?php
                        echo htmlspecialchars(
                            $news["image"]
                        );
                        ?>"
                        class="current-image"
                    >

                <?php else: ?>

                    <p style="color:#777;">
                        ไม่มีรูปภาพ
                    </p>

                <?php endif; ?>

            </div>


            <!-- รูปใหม่ -->

            <div class="form-group">

                <label>
                    เปลี่ยนรูปภาพ
                </label>


                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                >


                <small>
                    ถ้าไม่เลือกไฟล์
                    ระบบจะใช้รูปเดิม
                </small>

            </div>


            <!-- แหล่งข่าว -->

            <div class="form-group">

                <label>
                    แหล่งข่าว
                </label>


                <input
                    type="text"
                    name="source"
                    value="<?php
                    echo htmlspecialchars(
                        $news["source"] ?? ""
                    );
                    ?>"
                >

            </div>


            <!-- สถานะ -->

            <div class="form-group">

                <label>
                    สถานะ
                </label>


                <select name="status">

                    <option
                        value="draft"
                        <?php
                        if (
                            $news["status"]
                            === "draft"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        ร่าง
                    </option>


                    <option
                        value="published"
                        <?php
                        if (
                            $news["status"]
                            === "published"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        เผยแพร่
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="button"
            >
                💾 บันทึกการแก้ไข
            </button>


        </form>

    </div>


</main>


<script src="admin-inline-images.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-inline-images.js"); ?>" defer></script>
<script src="admin-transition.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.js"); ?>" defer></script>
</body>

</html>