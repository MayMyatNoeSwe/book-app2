<?php
// includes/functions.php
// Global Helper Functions for the Book Library System

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/**
 * Get the list of available book categories
 * @return array
 */
function getCategories(): array
{
    static $categories = null;

    if ($categories !== null) {
        return $categories;
    }

    $fallbackCategories = [
        'Fiction',
        'Non-Fiction',
        'Mystery',
        'Romance',
        'Sci-Fi',
        'Fantasy',
        'Biography',
        'History',
        'Self-Help',
        'Children',
        'Horror',
        'Thriller',
        'Poetry',
        'Uncategorized'
    ];

    try {
        $config = include APP_ROOT . '/config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND TRIM(category) <> '' ORDER BY category ASC");
        $dbCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($dbCategories)) {
            $categories = array_values(array_unique(array_map('trim', $dbCategories)));
            return $categories;
        }
    } catch (Throwable $e) {
        // Fall back to the static list if the database is unavailable.
    }

    $categories = $fallbackCategories;
    return $categories;
}

/**
 * Flash message helper - set a one-time message
 * @param string $message
 * @param string $type 'success', 'danger', 'warning', 'info' (Bootstrap classes)
 */
function setFlashMessage(string $message, string $type = 'info'): void
{
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
}

/**
 * Display and clear flash message using SweetAlert2
 */
function displayFlashMessage(): void
{
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        
        // Map Bootstrap alert types to SweetAlert2 icon types
        $iconMap = [
            'success' => 'success',
            'danger' => 'error',
            'warning' => 'warning',
            'info' => 'info'
        ];
        
        $icon = $iconMap[$msg['type']] ?? 'info';
        
        // Output JavaScript to show SweetAlert2
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '{$icon}',
                title: '" . ($icon === 'error' ? 'Error!' : ($icon === 'success' ? 'Success!' : 'Notice')) . "',
                html: '" . addslashes($msg['text']) . "',
                confirmButtonText: 'OK',
                confirmButtonColor: '#2e8a40',
                timer: 5000,
                timerProgressBar: true,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        });
        </script>";
        
        unset($_SESSION['flash_message']);
    }
}

/**
 * Show SweetAlert2 confirmation dialog (use in JavaScript)
 * Returns JavaScript code to be echoed
 */
function getSweetAlertConfirm(string $title, string $text, string $confirmText = 'Yes', string $cancelText = 'No'): string
{
    return "
    Swal.fire({
        title: '{$title}',
        text: '{$text}',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e8a40',
        cancelButtonColor: '#d33',
        confirmButtonText: '{$confirmText}',
        cancelButtonText: '{$cancelText}'
    })
    ";
}

/**
 * Redirect to a URL
 * @param string $path
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Check if user is logged in (wrapper)
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 */
function isAdmin(): bool
{
    return $_SESSION['role'] ?? '' === 'admin';
}

/**
 * Sanitize output (prevent XSS)
 * @param string $data
 * @return string
 */
function e($data): string
{
    if ($data === null || $data === '') {
        return '';
    }
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Get base URL for assets (helpful for subfolders or live hosting)
 * @return string
 */
function baseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . $host . $script, '/');
}

/**
 * Format date nicely
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate(?string $date, string $format = 'M j, Y'): string
{
    if ($date === null || $date === '' || $date === '0000-00-00') {
        return '<em>Not returned</em>';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '<em>Invalid date</em>';
}

/**
 * Generate random book ID (if needed outside Book class)
 * @return string
 */
function generateBookId(): string
{
    return 'book_' . uniqid();
}

/**
 * Check if a book is overdue
 * @param string $dueDate YYYY-MM-DD
 * @return bool
 */
function isOverdue(string $dueDate): bool
{
    return strtotime($dueDate) < time();
}

/**
 * Calculate overdue days
 * @param string $dueDate
 * @return int
 */
function overdueDays(string $dueDate): int
{
    $due = strtotime($dueDate);
    $now = time();
    if ($due >= $now) return 0;
    return floor(($now - $due) / (60 * 60 * 24));
}
function myanmarDateTime(string $date, string $format = 'Y-m-d', string $time = 'H:i:s'): string
{
    //Asia-Yangon
    $date = new DateTime($date);
    $date->setTimezone(new DateTimeZone('Asia/Yangon'));
    return $date->format($format . ' ' . $time);
}

/**
 * Detect gender from author name based on common patterns
 * @param string $name
 * @return string 'male' or 'female'
 */
function detectGender(string $name): string
{
    $name = strtolower($name);
    
    // Check for titles
    if (preg_match('/\b(mrs?|miss|ms)\b\.?\s/i', $name)) {
        return 'female';
    }
    if (preg_match('/\b(mr|sir|lord)\b\.?\s/i', $name)) {
        return 'male';
    }
    
    // Common female name patterns and endings
    $femalePatterns = [
        '/\b(jane|mary|elizabeth|emily|sarah|anna|emma|sophia|olivia|charlotte|amelia|harper|evelyn|abigail|ella|scarlett|grace|chloe|victoria|riley|aria|lily|aurora|violet|hannah|natalie|zoe|samantha|claire|audrey|lucy|bella|alice|julia|sophie|katherine|margaret|virginia|dorothy|helen|ruth|joan|judith|barbara|nancy|susan|carol|diane|rebecca|laura|linda|patricia|maria|jennifer|michelle|lisa|karen|betty|sandra|ashley|kimberly|donna|emily|carol|sharon|cynthia|kathleen|amy|angela|melissa|brenda|pamela|catherine|christine|janet|deborah|rachel|carolyn|heather|diane|joyce|evelyn|joan|judy|andrea|marie|jacqueline|gloria|teresa|ann|sara|madison|kathryn|frances|jean|alice|judy|rose|theresa|beverly|denise|tammy|irene|jane|lori|rachel|marilyn|andrea|kathryn|louise|sara|anne|jacqueline|wanda|bonnie|julia|ruby|lois|tina|phyllis|norma|paula|diana|annie|lillian|emily|robin|peggy|crystal|gladys|rita|dawn|connie|florence|tracy|edna|tiffany|carmen|rosa|cindy|grace|wendy|victoria|edith|kim|sherry|sylvia|josephine|thelma|shannon|sheila|ethel|ellen|elaine|marjorie|carrie|charlotte|monica|esther|pauline|emma|juanita|anita|rhonda|hazel|amber|eva|debbie|april|leslie|clara|lucille|jamie|joanne|eleanor|valerie|danielle|megan|alicia|suzanne|michele|gail|bertha|darlene|veronica|jill|erin|geraldine|lauren|cathy|joann|lorraine|lynn|sally|regina|erica|beatrice|dolores|bernice|audrey|yvonne|annette|june|marion|dana|stacy|ana|renee|ida|vivian|roberta|holly|brittany|melanie|loretta|yolanda|jeanette|laurie|katie|kristen|vanessa|alma|sue|elsie|beth|jeanne|vicki|carla|tara|rosemary|eileen|terri|gertrude|lucy|tonya|ella|stacey|wilma|gina|kristin|jessie|natalie|agnes|vera|charlene|bessie|delores|melinda|pearl|arlene|maureen|colleen|allison|tamara|joy|georgia|constance|lillie|claudia|jackie|marcia|tanya|nellie|minnie|marlene|heidi|glenda|lydia|viola|courtney|marian|stella|caroline|dora|jo|vickie|mattie|terry|maxine|irma|mabel|marsha|myrtle|lena|christy|deanna|patsy|hilda|gwendolyn|jennie|nora|margie|nina|cassandra|leah|penny|kay|priscilla|naomi|carole|brandy|olga|billie|dianne|tracey|leona|jenny|felicia|sonia|miriam|velma|becky|bobbie|violet|kristina|toni|misty|mae|shelly|daisy|ramona|sherri|erika|katrina|claire)\b/i',
        '/\b\w+(a|ia|ina|elle|ette|een|lyn|anne|ette|ine|ara|ora|ita|isa|essa|etta|ella|bella|rosa|rita|nina|lena|gina|tina|dina|mina|fina|lina|rina|vina|wina|zina)\b$/i'
    ];
    
    foreach ($femalePatterns as $pattern) {
        if (preg_match($pattern, $name)) {
            return 'female';
        }
    }
    
    // Default to male if no female indicators found
    return 'male';
}

/**
 * Generate gender-appropriate avatar URL with realistic images
 * @param string $name Author name
 * @param int $size Image size in pixels
 * @return string Avatar URL
 */
function getAuthorAvatarUrl(string $name, int $size = 120): string
{
    $gender = detectGender($name);
    
    // Generate a seed based on the name for consistent avatars
    $seed = md5($name);
    
    // Use randomuser.me API for realistic profile photos
    // This service provides real-looking profile pictures
    if ($gender === 'female') {
        return "https://randomuser.me/api/portraits/women/" . (hexdec(substr($seed, 0, 2)) % 100) . ".jpg";
    } else {
        return "https://randomuser.me/api/portraits/men/" . (hexdec(substr($seed, 0, 2)) % 100) . ".jpg";
    }
}

/**
 * Get dummy book cover image URL
 * Uses an external image API for generic book covers
 * 
 * @param string $title Book title
 * @param string $author Book author
 * @param int $width Image width (default: 400)
 * @param int $height Image height (default: 600)
 * @return string URL to dummy book cover
 */
function getDummyBookCover(string $title = 'Book Title', string $author = 'Author', int $width = 400, int $height = 600): string
{
    $width = max(120, min(800, (int)$width));
    $height = max(180, min(1200, (int)$height));
    $innerWidth = $width - 36;
    $innerHeight = $height - 36;
    $seed = md5($title . '|' . $author);
    $palette = [
        ['#3D405B', '#E07A5F'],
        ['#2E8A40', '#81B29A'],
        ['#6C5CE7', '#F2CC8F'],
        ['#355C7D', '#C06C84'],
        ['#264653', '#E9C46A']
    ];
    $colors = $palette[hexdec(substr($seed, 0, 2)) % count($palette)];
    $titleText = htmlspecialchars(mb_strimwidth(trim($title) ?: 'Book Title', 0, 36, '...'), ENT_QUOTES, 'UTF-8');
    $authorText = htmlspecialchars(mb_strimwidth(trim($author) ?: 'Unknown Author', 0, 28, '...'), ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
  <defs>
    <linearGradient id="coverGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$colors[0]}"/>
      <stop offset="100%" stop-color="{$colors[1]}"/>
    </linearGradient>
  </defs>
  <rect width="100%" height="100%" rx="24" fill="url(#coverGradient)"/>
  <rect x="18" y="18" width="{$innerWidth}" height="{$innerHeight}" rx="24" fill="none" stroke="rgba(255,255,255,0.2)"/>
  <text x="50%" y="42%" text-anchor="middle" fill="#FFFFFF" font-family="Georgia, serif" font-size="28" font-weight="700">My Library</text>
  <text x="50%" y="56%" text-anchor="middle" fill="#F8F5F0" font-family="Arial, sans-serif" font-size="22">{$titleText}</text>
  <text x="50%" y="66%" text-anchor="middle" fill="#F8F5F0" font-family="Arial, sans-serif" font-size="16">{$authorText}</text>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

/**
 * Get book cover URL with fallback to dummy image
 * 
 * @param mixed $book Book object or cover image string
 * @param string $title Book title (for dummy cover)
 * @param string $author Book author (for dummy cover)
 * @return string URL to book cover or dummy image
 */
function getBookCoverUrl($book, string $title = '', string $author = ''): string
{
    // If $book is an object with getCoverImage method
    if (is_object($book) && method_exists($book, 'getCoverImage')) {
        $coverImage = $book->getCoverImage();
        if ($coverImage && file_exists(APP_ROOT . '/public/uploads/covers/' . $coverImage)) {
            return baseUrl() . '/public/uploads/covers/' . $coverImage;
        }
        // Get title and author from book object for dummy cover
        $title = method_exists($book, 'getTitle') ? $book->getTitle() : $title;
        $author = method_exists($book, 'getAuthor') ? $book->getAuthor() : $author;
    }
    // If $book is a string (cover image filename)
    elseif (is_string($book) && !empty($book)) {
        if (file_exists(APP_ROOT . '/public/uploads/covers/' . $book)) {
            return baseUrl() . '/public/uploads/covers/' . $book;
        }
    }
    
    // Return dummy cover if no valid cover found
    return getDummyBookCover($title, $author);
}
