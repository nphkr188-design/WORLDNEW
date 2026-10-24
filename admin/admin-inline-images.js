document.addEventListener("DOMContentLoaded", function () {
    const contentField = document.querySelector('textarea[name="content"]');
    const csrfField = document.querySelector('input[name="inline_image_csrf"]');

    if (!contentField || !csrfField) {
        return;
    }

    const toolbar = document.createElement("div");
    toolbar.className = "inline-image-toolbar";

    const uploadButton = document.createElement("button");
    uploadButton.type = "button";
    uploadButton.className = "inline-image-button";
    uploadButton.textContent = "แทรกรูปภาพในเนื้อข่าว";

    const videoButton = document.createElement("button");
    videoButton.type = "button";
    videoButton.className = "inline-image-button";
    videoButton.textContent = "แทรกวิดีโอในเนื้อข่าว";

    const help = document.createElement("span");
    help.className = "inline-image-help";
    help.textContent = "เลือกแทรกรูปหรือวิดีโอตรงตำแหน่งเคอร์เซอร์ (วิดีโอ MP4, WebM หรือ OGG ไม่เกิน 50 MB)";

    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/jpeg,image/png,image/webp";
    fileInput.hidden = true;
    fileInput.setAttribute("aria-label", "เลือกภาพประกอบข่าว");

    const videoInput = document.createElement("input");
    videoInput.type = "file";
    videoInput.accept = "video/mp4,video/webm,video/ogg";
    videoInput.hidden = true;
    videoInput.setAttribute("aria-label", "เลือกวิดีโอประกอบข่าว");

    toolbar.append(uploadButton, videoButton, help, fileInput, videoInput);
    contentField.parentNode.insertBefore(toolbar, contentField);

    uploadButton.addEventListener("click", function () {
        fileInput.click();
    });

    videoButton.addEventListener("click", function () {
        videoInput.click();
    });

    async function uploadMedia(input, options) {
        const file = input.files[0];
        if (!file) {
            return;
        }

        if (file.size > options.maxSize) {
            window.alert(options.sizeError);
            input.value = "";
            return;
        }

        uploadButton.disabled = true;
        videoButton.disabled = true;
        uploadButton.textContent = "กำลังอัปโหลด...";
        videoButton.textContent = "กำลังอัปโหลด...";

        try {
            const formData = new FormData();
            formData.append("csrf_token", csrfField.value);
            formData.append(options.field, file);

            const response = await fetch(options.endpoint, {
                method: "POST",
                body: formData,
                credentials: "same-origin",
                headers: { "Accept": "application/json" },
            });
            const result = await response.json();

            if (!response.ok || typeof result.file !== "string") {
                throw new Error(result.message || options.uploadError);
            }

            const marker = `\n[[${options.marker}:${result.file}]]\n`;
            const start = contentField.selectionStart;
            const end = contentField.selectionEnd;
            contentField.setRangeText(marker, start, end, "end");
            contentField.focus();
        } catch (error) {
            window.alert(error instanceof Error ? error.message : options.uploadError);
        } finally {
            uploadButton.disabled = false;
            videoButton.disabled = false;
            uploadButton.textContent = "แทรกรูปภาพในเนื้อข่าว";
            videoButton.textContent = "แทรกวิดีโอในเนื้อข่าว";
            input.value = "";
        }
    }

    fileInput.addEventListener("change", function () {
        uploadMedia(fileInput, {
            endpoint: "upload-inline-image.php",
            field: "inline_image",
            marker: "IMAGE",
            maxSize: 5 * 1024 * 1024,
            sizeError: "รูปภาพต้องมีขนาดไม่เกิน 5 MB",
            uploadError: "ไม่สามารถอัปโหลดภาพได้ กรุณาตรวจสอบไฟล์แล้วลองใหม่",
        });
    });

    videoInput.addEventListener("change", function () {
        uploadMedia(videoInput, {
            endpoint: "upload-inline-video.php",
            field: "inline_video",
            marker: "VIDEO",
            maxSize: 50 * 1024 * 1024,
            sizeError: "วิดีโอต้องมีขนาดไม่เกิน 50 MB",
            uploadError: "ไม่สามารถอัปโหลดวิดีโอได้ กรุณาตรวจสอบไฟล์แล้วลองใหม่",
        });
    });
});
