<?php

require_once __DIR__ . "/require-admin.php";

require_once "../config/database.php";

$error = "";
if (!isset($_SESSION["inline_image_csrf"])) {
    $_SESSION["inline_image_csrf"] = bin2hex(random_bytes(32));
}

/* =========================
   ดึงหมวดหมู่ข่าว
========================= */

$stmt = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   เมื่อกดบันทึกข่าว
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $content = trim($_POST["content"] ?? "");
    $source = trim($_POST["source"] ?? "");
    $status = $_POST["status"] ?? "draft";

    $imageName = null;


    /* =========================
       ตรวจสอบข้อมูล
    ========================= */

    if (
        $title === "" ||
        $category_id === "" ||
        $content === ""
    ) {

        $error = "กรุณากรอกข้อมูลที่จำเป็นให้ครบ";

    } elseif (!in_array($status, ["draft", "published"])) {

        $error = "สถานะข่าวไม่ถูกต้อง";

    } else {

        /* =========================
           อัปโหลดรูปภาพ
        ========================= */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

                $error = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";

            } else {

                $file = $_FILES["image"];


                /* ขนาดไฟล์ไม่เกิน 5 MB */

                if ($file["size"] > 5 * 1024 * 1024) {

                    $error = "รูปภาพต้องมีขนาดไม่เกิน 5 MB";

                } else {

                    /* ตรวจสอบชนิดไฟล์จริง */

                    $allowedTypes = [
                        "image/jpeg",
                        "image/png",
                        "image/webp"
                    ];

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);

                    $mimeType = finfo_file(
                        $finfo,
                        $file["tmp_name"]
                    );

                    finfo_close($finfo);


                    if (!in_array($mimeType, $allowedTypes)) {

                        $error = "อนุญาตเฉพาะ JPG, PNG และ WEBP";

                    } else {

                        /* =========================
                           สร้างนามสกุลไฟล์
                        ========================= */

                        $extension = match ($mimeType) {

                            "image/jpeg" => "jpg",
                            "image/png" => "png",
                            "image/webp" => "webp"

                        };


                        /* =========================
                           สร้างชื่อไฟล์ใหม่
                        ========================= */

                        $imageName =
                            bin2hex(random_bytes(16))
                            . "."
                            . $extension;


                        /* =========================
                           ตำแหน่งเก็บรูป
                        ========================= */

                        $uploadDir = "../assets/images/";


                        if (!is_dir($uploadDir)) {

                            mkdir(
                                $uploadDir,
                                0755,
                                true
                            );
                        }


                        $uploadPath =
                            $uploadDir . $imageName;


                        /* =========================
                           ย้ายไฟล์
                        ========================= */

                        if (
                            !move_uploaded_file(
                                $file["tmp_name"],
                                $uploadPath
                            )
                        ) {

                            $error = "อัปโหลดรูปภาพไม่สำเร็จ";
                        }
                    }
                }
            }
        }


        /* =========================
           บันทึกข่าวลงฐานข้อมูล
        ========================= */

        if ($error === "") {

            $sql = "
                INSERT INTO news
                (
                    category_id,
                    author_id,
                    title,
                    content,
                    image,
                    source,
                    status
                )
                VALUES
                (
                    :category_id,
                    :author_id,
                    :title,
                    :content,
                    :image,
                    :source,
                    :status
                )
            ";


            $stmt = $pdo->prepare($sql);


            $stmt->execute([
                ":category_id" => $category_id,
                ":author_id" => $_SESSION["user_id"],
                ":title" => $title,
                ":content" => $content,
                ":image" => $imageName,
                ":source" => $source,
                ":status" => $status
            ]);


            header("Location: news.php?success=1");
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

    <title>เพิ่มข่าว - ข่าวสารภายในประเทศ CMS</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family:
                Arial,
                "Tahoma",
                sans-serif;

            background: #f5f7fa;

            color: #222;

            min-height: 100vh;
        }


        /* =========================
           HEADER
        ========================= */

        .header {

            background: #173b6c;

            color: white;

            padding: 20px 40px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;
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

            transition: 0.2s;
        }


        .back:hover {

            background: #3972b5;
        }


        /* =========================
           CONTAINER
        ========================= */

        .container {

            max-width: 900px;

            margin: auto;

            padding: 40px 20px;
        }


        /* =========================
           BOX
        ========================= */

        .box {

            background: white;

            padding: 35px;

            border-radius: 18px;

            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.06);
        }


        h1 {

            margin-bottom: 10px;

            font-size: 28px;
        }


        .description {

            color: #777;

            margin-bottom: 30px;

            line-height: 1.6;
        }


        /* =========================
           ERROR
        ========================= */

        .error {

            background: #ffe5e5;

            color: #c62828;

            padding: 14px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            border: 1px solid #f5bcbc;
        }


        /* =========================
           FORM
        ========================= */

        .form-group {

            margin-bottom: 22px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

            color: #333;
        }


        .required {

            color: #d32f2f;
        }


        input,
        select,
        textarea {

            width: 100%;

            padding: 13px 14px;

            border: 1px solid #ddd;

            border-radius: 10px;

            font-size: 15px;

            background: white;

            transition: 0.2s;
        }


        input:focus,
        select:focus,
        textarea:focus {

            outline: none;

            border-color: #173b6c;

            box-shadow:
                0 0 0 3px
                rgba(23, 59, 108, 0.08);
        }


        textarea {

            min-height: 260px;

            resize: vertical;

            line-height: 1.7;
        }


        input[type="file"] {

            padding: 10px;

            background: #fafafa;

            cursor: pointer;
        }


        .help-text {

            display: block;

            margin-top: 8px;

            color: #888;

            font-size: 13px;
        }


        /* =========================
           IMAGE PREVIEW
        ========================= */

        .image-preview-box {

            margin-top: 15px;

            display: none;
        }


        .image-preview-title {

            font-size: 14px;

            font-weight: bold;

            color: #555;

            margin-bottom: 8px;
        }


        #imagePreview {

            width: 100%;

            max-width: 500px;

            height: 280px;

            object-fit: cover;

            border-radius: 14px;

            border: 1px solid #ddd;

            display: block;
        }


        /* =========================
           BUTTON
        ========================= */

        .button {

            border: none;

            background: #173b6c;

            color: white;

            padding: 14px 25px;

            border-radius: 10px;

            cursor: pointer;

            font-size: 16px;

            font-weight: bold;

            transition: 0.2s;

            width: 100%;
        }


        .button:hover {

            background: #295d9b;

            transform: translateY(-1px);
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 768px) {

            .header {

                padding: 18px 20px;

                flex-direction: column;

                align-items: flex-start;
            }


            .container {

                padding: 25px 15px;
            }


            .box {

                padding: 22px;
            }


            h1 {

                font-size: 24px;
            }


            #imagePreview {

                height: 220px;
            }

        }

        :root {
            color-scheme: light;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #202733;
            background: #f6f7f9;
        }

        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: #f6f7f9;
            color: #202733;
        }

        .header {
            min-height: 70px;
            padding: 12px max(20px, calc((100vw - 1100px) / 2));
            border-bottom: 1px solid #e9ebef;
            background: #fff;
            color: #202733;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 11px;
            font-size: 16px;
            letter-spacing: -0.02em;
        }

        .logo::before {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 11px;
            background: #fae9eb;
            color: #b4232c;
            content: "ข";
            font-size: 18px;
        }

        .back {
            border: 1px solid #e6e8ed;
            background: #fff;
            color: #4e5663;
        }

        .back:hover {
            border-color: #f1c8cc;
            background: #fff7f7;
            color: #b4232c;
        }

        .container {
            max-width: 900px;
            padding: 38px 20px 56px;
        }

        .box {
            padding: clamp(22px, 5vw, 38px);
            border: 1px solid #eceef1;
            border-radius: 17px;
            box-shadow: 0 8px 28px rgba(23, 31, 43, 0.045);
        }

        h1 {
            color: #1e2530;
            font-size: clamp(24px, 4vw, 30px);
            letter-spacing: -0.04em;
        }

        .description {
            margin-bottom: 26px;
            color: #747d8a;
            font-size: 14px;
        }

        .error {
            border: 1px solid #f1c8cc;
            background: #fff5f5;
            color: #a51e28;
        }

        form[enctype="multipart/form-data"] {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 20px;
        }

        .form-group {
            min-width: 0;
            margin: 0;
        }

        .form-group:nth-of-type(1),
        .form-group:nth-of-type(3),
        .form-group:nth-of-type(4),
        form[enctype="multipart/form-data"] > .button {
            grid-column: 1 / -1;
        }

        label {
            margin-bottom: 7px;
            color: #394150;
            font-size: 13px;
            font-weight: 650;
        }

        input,
        select,
        textarea {
            border-color: #e2e5ea;
            border-radius: 10px;
            color: #252c37;
            font: inherit;
            font-size: 14px;
        }

        input,
        select {
            min-height: 45px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #c45a5d;
            box-shadow: 0 0 0 3px rgba(185, 45, 45, 0.09);
        }

        textarea {
            min-height: 260px;
        }

        input[type="file"] {
            min-height: 0;
            padding: 11px;
            border-style: dashed;
            background: #fafbfc;
        }

        .help-text {
            color: #858d99;
            font-size: 12px;
        }

        .image-preview-title {
            color: #505866;
            font-size: 13px;
        }

        #imagePreview {
            max-width: 100%;
            border-color: #e7e9ed;
            border-radius: 12px;
        }

        .button {
            width: auto;
            justify-self: start;
            min-height: 44px;
            padding: 0 20px;
            border-radius: 10px;
            background: #b4232c;
            font-size: 14px;
        }

        .button:hover {
            background: #991b24;
        }

        @media (max-width: 640px) {
            .header {
                min-height: 64px;
                padding: 12px 16px;
            }

            .container {
                padding: 24px 14px 36px;
            }

            form[enctype="multipart/form-data"] {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .form-group:nth-of-type(1),
            .form-group:nth-of-type(3),
            .form-group:nth-of-type(4),
            form[enctype="multipart/form-data"] > .button {
                grid-column: auto;
            }

            form[enctype="multipart/form-data"] > .button {
                width: 100%;
            }
        }

    </style>
    <link rel="stylesheet" href="admin-transition.css?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.css"); ?>">
    <link rel="stylesheet" href="admin-inline-images.css?v=<?php echo (int) filemtime(__DIR__ . "/admin-inline-images.css"); ?>">

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="header">

    <div class="logo">
        ข่าวสารภายในประเทศ
    </div>


    <a
        href="dashboard.php"
        class="back"
    >
        กลับแดชบอร์ด
    </a>

</header>


<!-- =========================
     MAIN
========================= -->

<main class="container">

    <div class="box">

        <h1>
            📝 เพิ่มข่าวใหม่
        </h1>


        <p class="description">
            กรอกข้อมูลข่าว เลือกรูปภาพ และเลือกสถานะเพื่อเผยแพร่ข่าว
        </p>


        <?php if ($error !== ""): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             FORM
        ========================= -->

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
                    placeholder="กรอกหัวข้อข่าว"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST["title"] ?? ""
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

                    <option value="">
                        -- เลือกหมวดหมู่ --
                    </option>


                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?php
                                echo (int)$category["id"];
                            ?>"
                            <?php
                                if (
                                    isset(
                                        $_POST["category_id"]
                                    )
                                    &&
                                    $_POST["category_id"]
                                    ==
                                    $category["id"]
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


            <!-- เนื้อข่าว -->

            <div class="form-group">

                <label>
                    เนื้อหาข่าว
                    <span class="required">*</span>
                </label>

                <textarea
                    name="content"
                    placeholder="เขียนรายละเอียดข่าว..."
                    required
                ><?php
                    echo htmlspecialchars(
                        $_POST["content"] ?? ""
                    );
                ?></textarea>

            </div>


            <!-- =========================
                 รูปภาพ
            ========================= -->

            <div class="form-group">

                <label>
                    🖼️ รูปภาพข่าว
                </label>


                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    id="imageInput"
                >


                <small class="help-text">
                    รองรับ JPG, PNG และ WEBP ขนาดไม่เกิน 5 MB
                </small>


                <!-- ตัวอย่างรูป -->

                <div
                    class="image-preview-box"
                    id="previewBox"
                >

                    <div class="image-preview-title">
                        👁️ ตัวอย่างรูปภาพ
                    </div>


                    <img
                        id="imagePreview"
                        src=""
                        alt="ตัวอย่างรูปภาพ"
                    >

                </div>

            </div>


            <!-- แหล่งข่าว -->

            <div class="form-group">

                <label>
                    แหล่งข่าว
                </label>

                <input
                    type="text"
                    name="source"
                    placeholder="เช่น Reuters, BBC, CNN"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST["source"] ?? ""
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
                            ($_POST["status"] ?? "")
                            === "draft"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        📝 ฉบับร่าง
                    </option>


                    <option
                        value="published"
                        <?php
                        if (
                            ($_POST["status"] ?? "")
                            === "published"
                        ) {
                            echo "selected";
                        }
                        ?>
                    >
                        🌐 เผยแพร่ทันที
                    </option>

                </select>

            </div>


            <!-- ปุ่ม -->

            <button
                type="submit"
                class="button"
            >
                💾 บันทึกข่าว
            </button>


        </form>

    </div>

</main>


<!-- =========================
     IMAGE PREVIEW JAVASCRIPT
========================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const imageInput =
            document.getElementById(
                "imageInput"
            );

        const imagePreview =
            document.getElementById(
                "imagePreview"
            );

        const previewBox =
            document.getElementById(
                "previewBox"
            );


        if (
            !imageInput ||
            !imagePreview ||
            !previewBox
        ) {
            return;
        }


        imageInput.addEventListener(
            "change",
            function () {

                const file =
                    this.files[0];


                if (!file) {

                    previewBox.style.display =
                        "none";

                    imagePreview.src = "";

                    return;
                }


                /* ตรวจสอบว่าเป็นรูป */

                if (
                    !file.type.startsWith(
                        "image/"
                    )
                ) {

                    alert(
                        "กรุณาเลือกไฟล์รูปภาพ"
                    );

                    this.value = "";

                    previewBox.style.display =
                        "none";

                    return;
                }


                /* ตรวจสอบขนาด */

                if (
                    file.size >
                    5 * 1024 * 1024
                ) {

                    alert(
                        "รูปภาพต้องมีขนาดไม่เกิน 5 MB"
                    );

                    this.value = "";

                    previewBox.style.display =
                        "none";

                    return;
                }


                /* แสดงตัวอย่าง */

                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        imagePreview.src =
                            event.target.result;

                        previewBox.style.display =
                            "block";
                    };


                reader.readAsDataURL(file);

            }
        );

    }
);

</script>

<script src="admin-inline-images.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-inline-images.js"); ?>" defer></script>
<script src="admin-transition.js?v=<?php echo (int) filemtime(__DIR__ . "/admin-transition.js"); ?>" defer></script>
</body>

</html>