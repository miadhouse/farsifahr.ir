<?php
require_once(__DIR__ . '/config/config.php');

$json = '{
  "title": "تابلوهای راهنمایی و رانندگی آلمان",
  "source": "https://www.fuehrerscheine.de/verkehrsrecht/verkehrszeichen/",
  "categories": [
    {
      "id": "gefahrenzeichen",
      "name_de": "Gefahrenzeichen",
      "name_fa": "تابلوهای خطر",
      "description_fa": "تابلوهای خطر از مهم‌ترین تابلوهای راهنمایی و رانندگی هستند که نقاط خطرناک در ترافیک را نشان می‌دهند. رانندگان باید سرعت خود را کاهش داده و مراقب باشند.",
      "signs": [
        {"number": "101", "name_de": "Gefahrstelle", "name_fa": "محل خطر", "description_fa": "این تابلو نشان‌دهنده یک نقطه خطرناک است. باید سرعت را کاهش داده، مراقب بوده و آماده ترمز باشید.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Gefahrenstelle-Zeichen-101-e1574331400128.png"},
        {"number": "102", "name_de": "Kreuzung oder Einmündung mit Vorfahrt von rechts", "name_fa": "تقاطع یا پیوندگاه با اولویت از راست", "description_fa": "این تابلو هشدار می‌دهد که یک تقاطع یا پیوندگاه در پیش است و قانون «راست اولویت دارد» در آنجا اعمال می‌شود.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/verkehrszeichen-102.svg"},
        {"number": "103-10", "name_de": "Kurve (links)", "name_fa": "پیچ (چپ)", "description_fa": "این تابلو هشدار می‌دهد که یک پیچ خطرناک به سمت چپ در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/103-10-1-342x300.png"},
        {"number": "103-20", "name_de": "Kurve (rechts)", "name_fa": "پیچ (راست)", "description_fa": "این تابلو هشدار می‌دهد که یک پیچ خطرناک به سمت راست در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen-103-20-Kurve-rehts-342x300.png"},
        {"number": "105-10", "name_de": "Doppelkurve (zunächst links)", "name_fa": "پیچ دوگانه (ابتدا چپ)", "description_fa": "این تابلو هشدار می‌دهد که یک پیچ دوگانه در پیش است که با یک پیچ به سمت چپ شروع می‌شود.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/105-10-1-342x300.png"},
        {"number": "105-20", "name_de": "Doppelkurve (zunächst rechts)", "name_fa": "پیچ دوگانه (ابتدا راست)", "description_fa": "این تابلو هشدار می‌دهد که یک پیچ دوگانه در پیش است که با یک پیچ به سمت راست شروع می‌شود.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/105-20-Doppelkurve.png"},
        {"number": "108", "name_de": "Gefälle", "name_fa": "سراشیبی", "description_fa": "این تابلو نشان می‌دهد که یک سراشیبی در پیش است و درصد شیب را نیز مشخص می‌کند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/108-10-1-336x300.png"},
        {"number": "110", "name_de": "Steigung", "name_fa": "سربالایی", "description_fa": "این تابلو نشان می‌دهد که یک سربالایی در پیش است و درصد شیب را نیز مشخص می‌کند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/110-12-2-336x300.png"},
        {"number": "112", "name_de": "Unebene Fahrbahn", "name_fa": "جاده ناهموار", "description_fa": "این تابلو نشان می‌دهد که جاده ناهموار است و رانندگان باید احتیاط کنند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/112-1-342x300.png"},
        {"number": "113", "name_de": "Schnee- oder Eisglätte", "name_fa": "لغزندگی برف یا یخ", "description_fa": "این تابلو هشدار می‌دهد که جاده به دلیل برف یا یخ لغزنده است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_113_-_Glättegefahr-342x300.png"},
        {"number": "114", "name_de": "Schleudergefahr bei Nässe oder Schmutz", "name_fa": "خطر سُر خوردن در خیسی یا کثیفی", "description_fa": "این تابلو هشدار می‌دهد که در صورت خیس یا کثیف بودن جاده خطر سُر خوردن وجود دارد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/114-Schleudergefahr-341x300.png"},
        {"number": "115", "name_de": "Steinschlag", "name_fa": "ریزش سنگ", "description_fa": "این تابلو نشان می‌دهد که احتمال ریزش سنگ از کوه یا وجود سنگ روی جاده وجود دارد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_115-Steinschlag-342x300.png"},
        {"number": "116", "name_de": "Splitt, Schotter", "name_fa": "سنگریزه و شن", "description_fa": "این تابلو نشان می‌دهد که آسفالت جاده محکم نیست یا سنگریزه و شن روی جاده وجود دارد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/116_-_Splitt_Schotter-342x300.png"},
        {"number": "117", "name_de": "Seitenwind", "name_fa": "باد جانبی", "description_fa": "این تابلو هشدار می‌دهد که باد شدید جانبی و وزش باد وجود دارد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/117-10-1-341x300.png"},
        {"number": "120", "name_de": "Verengte Fahrbahn", "name_fa": "تنگ شدن جاده", "description_fa": "این تابلو نشان می‌دهد که جاده باریک‌تر می‌شود.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/120-1-342x300.png"},
        {"number": "121-10", "name_de": "Einseitig (rechts) verengte Fahrbahn", "name_fa": "تنگ شدن یک‌طرفه جاده (از راست)", "description_fa": "این تابلو نشان می‌دهد که جاده از سمت راست باریک‌تر می‌شود.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen-121-10-Einseitig-rechts-verengte-Fahrbahn.png"},
        {"number": "121-20", "name_de": "Einseitig (links) verengte Fahrbahn", "name_fa": "تنگ شدن یک‌طرفه جاده (از چپ)", "description_fa": "این تابلو نشان می‌دهد که جاده از سمت چپ باریک‌تر می‌شود.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/121-20-einseitig_links_verengte_Fahrbahn.svg"},
        {"number": "123", "name_de": "Baustelle", "name_fa": "کارگاه ساختمانی", "description_fa": "این تابلو هشدار می‌دهد که در جاده کارهای ساختمانی در حال انجام است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/123-bauselle-341x300.png"},
        {"number": "124", "name_de": "Stau", "name_fa": "ترافیک", "description_fa": "این تابلو نشان می‌دهد که احتمال ترافیک وجود دارد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/124_-_Stau-342x300.png"},
        {"number": "125", "name_de": "Gegenverkehr", "name_fa": "ترافیک مقابل", "description_fa": "این تابلو هشدار می‌دهد که ترافیک از سمت مقابل در حال آمدن است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/125-gegenverkehr-341x300.png"},
        {"number": "128", "name_de": "Bewegliche Brücke", "name_fa": "پل متحرک", "description_fa": "این تابلو هشدار می‌دهد که یک پل متحرک در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_128-Bewegliche-Brücke-1-342x300.png"},
        {"number": "129", "name_de": "Ufer", "name_fa": "کنار آب", "description_fa": "این تابلو هشدار می‌دهد که در نزدیکی آب قرار دارید.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_129-Ufer-1.svg"},
        {"number": "131", "name_de": "Lichtzeichenanlage", "name_fa": "چراغ راهنمایی", "description_fa": "این تابلو هشدار می‌دهد که یک چراغ راهنمایی در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_131-Lichtzeichenanlage-1-342x300.png"},
        {"number": "133", "name_de": "Fußgänger", "name_fa": "عابر پیاده", "description_fa": "این تابلو هشدار می‌دهد که تعداد زیادی عابر پیاده در نزدیکی جاده حضور دارند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/133-10-min-341x300.png"},
        {"number": "134", "name_de": "Fußgängerüberweg", "name_fa": "گذرگاه عابر پیاده", "description_fa": "این تابلو هشدار می‌دهد که یک گذرگاه عابر پیاده در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/134-10_-_Fußgängerüberweg.svg"},
        {"number": "136", "name_de": "Kinder", "name_fa": "کودکان", "description_fa": "این تابلو هشدار می‌دهد که کودکانی ممکن است در خیابان حضور داشته باشند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/136-10-Kinder-1-341x300.png"},
        {"number": "138", "name_de": "Radverkehr", "name_fa": "دوچرخه‌سوار", "description_fa": "این تابلو هشدار می‌دهد که دوچرخه‌سواران ممکن است از خیابان عبور کنند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_138-10-Radverkehr-1-342x300.png"},
        {"number": "140", "name_de": "Viehtrieb, Tiere", "name_fa": "رمه و حیوانات", "description_fa": "این تابلو هشدار می‌دهد که احتمال حضور حیوانات در جاده هست.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_140-20-ViehtriebTiere-2.svg"},
        {"number": "142", "name_de": "Wildwechsel", "name_fa": "عبور حیوانات وحشی", "description_fa": "این تابلو نشان می‌دهد که حیوانات وحشی ممکن است از خیابان عبور کنند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_142-10-Wildwechsel-1.svg"},
        {"number": "144", "name_de": "Flugbetrieb", "name_fa": "ترافیک هوایی", "description_fa": "این تابلو هشدار می‌دهد که در نزدیکی فرودگاه یا منطقه پرواز هستید.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_144-10-Flugbetrieb-1-342x300.png"},
        {"number": "145-15", "name_de": "Amphibienwanderung", "name_fa": "مهاجرت دوزیستان", "description_fa": "این تابلو نشان می‌دهد که دوزیستان در این محل مهاجرت می‌کنند.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_145-15-3-342x300.png"},
        {"number": "145", "name_de": "Kraftomnibusse", "name_fa": "اتوبوس", "description_fa": "این تابلو هشدار می‌دهد که باید انتظار افزایش تعداد اتوبوس‌ها را داشته باشید.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_145-Kraftomnibusse-1.svg"},
        {"number": "150", "name_de": "Bahnübergang mit Schranken", "name_fa": "تقاطع راه‌آهن با دروازه", "description_fa": "این تابلو نشان می‌دهد که یک تقاطع راه‌آهن با دروازه در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_150_-_Bahnübergang_mit_Schranken_oder_Halbschranken-1.svg"},
        {"number": "151", "name_de": "Unbeschrankter Bahnübergang", "name_fa": "تقاطع راه‌آهن بدون دروازه", "description_fa": "این تابلو نشان می‌دهد که یک تقاطع راه‌آهن بدون دروازه در پیش است.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/Zeichen_151_-_Unbeschrankter_Bahnübergang-1.svg"},
        {"number": "156", "name_de": "Bahnübergang mit dreistreifiger Bake", "name_fa": "تقاطع راه‌آهن با نشانگر سه‌خطه", "description_fa": "این تابلو هشدار تقاطع راه‌آهن می‌دهد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/156-Bahnübergang-mit-dreistreifiger-Bake.svg"},
        {"number": "157", "name_de": "Dreistreifige Bake", "name_fa": "نشانگر سه‌خطه", "description_fa": "این تابلو فاصله تا تقاطع راه‌آهن را نشان می‌دهد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/157-Dreistreifige-Bake-e1574266339524.png"},
        {"number": "159", "name_de": "Zweistreifige Bake", "name_fa": "نشانگر دوخطه", "description_fa": "این تابلو فاصله تا تقاطع راه‌آهن را نشان می‌دهد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/159-Zweistreifige-Bake-e1574266491573.png"},
        {"number": "162", "name_de": "Einstreifige Bake", "name_fa": "نشانگر یک‌خطه", "description_fa": "این تابلو فاصله تا تقاطع راه‌آهن را نشان می‌دهد.", "image_url": "https://www.fuehrerscheine.de/wp-content/uploads/2019/10/162-Einstreifig-Bake-e1574266144274.png"}
      ]
    }
  ]
}';

$data = json_decode($json, true);
$rootCategoryId = 1; // آموزش تابلو ها

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

foreach ($data['categories'] as $cat) {
    $slug = slugify($cat['name_fa']);
    
    // Insert Category
    $stmt = $pdo->prepare("INSERT INTO workshop_categories (parent_id, name, slug, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$rootCategoryId, $cat['name_fa'], $slug, $cat['description_fa']]);
    $catId = $pdo->lastInsertId();
    echo "Category created: " . $cat['name_fa'] . " (ID: $catId)\n";
    
    // Insert Signs
    foreach ($cat['signs'] as $sign) {
        $signSlug = slugify($sign['name_fa']);
        $content = "<h4>" . htmlspecialchars($sign['name_fa']) . "</h4><p>" . htmlspecialchars($sign['description_fa']) . "</p>";
        
        // Use image_url directly in Workshop image field as Laravel storage path needs local file
        // For now store URL if needed or we could download it. Let\'s store URL and update later
        $stmt = $pdo->prepare("INSERT INTO workshops (workshop_category_id, title, slug, content, image, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $stmt->execute([$catId, $sign['name_fa'], $signSlug, $content, $sign['image_url']]);
        echo "Sign created: " . $sign['name_fa'] . "\n";
    }
}
?>
