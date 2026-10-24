<?php

function renderNewsContent(
    string $content,
    string $imageBasePath,
    string $videoBasePath = "../assets/videos/"
): string
{
    $pattern = '/(\[\[(?:IMAGE|VIDEO):[a-f0-9]{32}\.(?:jpg|png|webp|mp4|webm|ogv)\]\])/';
    $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

    if ($parts === false) {
        return nl2br(htmlspecialchars($content, ENT_QUOTES, "UTF-8"));
    }

    $html = "";
    foreach ($parts as $index => $part) {
        if ($index % 2 === 1) {
            if (!preg_match('/^\[\[(IMAGE|VIDEO):([a-f0-9]{32}\.(?:jpg|png|webp|mp4|webm|ogv))\]\]$/', $part, $matches)) {
                $html .= htmlspecialchars($part, ENT_QUOTES, "UTF-8");
                continue;
            }

            if ($matches[1] === "VIDEO") {
                $videoPath = htmlspecialchars($videoBasePath . $matches[2], ENT_QUOTES, "UTF-8");
                $html .= '<figure class="news-inline-video"><video controls autoplay muted loop playsinline preload="metadata"><source src="' . $videoPath . '">เบราว์เซอร์ของคุณไม่รองรับวิดีโอ</video></figure>';
                continue;
            }

            $imagePath = htmlspecialchars($imageBasePath . $matches[2], ENT_QUOTES, "UTF-8");
            $html .= '<figure class="news-inline-image"><img src="' . $imagePath . '" alt="ภาพประกอบข่าว" loading="lazy"></figure>';
            continue;
        }

        $text = trim(str_replace(["\r\n", "\r"], "\n", $part));
        if ($text === "") {
            continue;
        }

        $paragraphs = preg_split('/\n[ \t]*\n+/', $text);
        if ($paragraphs === false) {
            $html .= nl2br(htmlspecialchars($text, ENT_QUOTES, "UTF-8"), false);
            continue;
        }

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === "") {
                continue;
            }

            $html .= '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, "UTF-8"), false) . '</p>';
        }
    }

    return $html;
}
