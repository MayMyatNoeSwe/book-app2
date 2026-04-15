<?php
// includes/functions.php
// Global Helper Functions for the Book Library System

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Ensure autoloader is available
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
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
        $configPath = APP_ROOT . '/config/database.php';
        if (!file_exists($configPath)) {
            error_log("getCategories: Config file not found at $configPath");
            $categories = $fallbackCategories;
            return $categories;
        }

        $config = include $configPath;
        if (!is_array($config)) {
            error_log("getCategories: Config inclusion did not return an array");
            $categories = $fallbackCategories;
            return $categories;
        }

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
        
        $allFound = [];

        // 1. Try to fetch from categories table
        try {
            $stmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
            if ($stmt) {
                $dbCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($dbCategories)) {
                    $allFound = array_merge($allFound, $dbCategories);
                }
            }
        } catch (Throwable $e) {
            error_log("getCategories Category Table Error: " . $e->getMessage());
        }

        // 2. Also fetch DISTINCT categories from books table to ensure none are missed
        try {
            $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND TRIM(category) <> '' ORDER BY category ASC");
            if ($stmt) {
                $bookCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($bookCategories)) {
                    $allFound = array_merge($allFound, $bookCategories);
                }
            }
        } catch (Throwable $e) {
            error_log("getCategories Books Table Error: " . $e->getMessage());
        }
        
        if (!empty($allFound)) {
            // Clean, unique, and sort
            $res = array_map('trim', $allFound);
            $res = array_filter($res);
            $res = array_unique($res);
            sort($res);
            $categories = $res;
            return $categories;
        }
        
    } catch (Throwable $e) {
        error_log("getCategories Master Error: " . $e->getMessage());
        if (ini_get('display_errors')) {
             echo "<!-- getCategories Master DB Error: " . htmlspecialchars($e->getMessage()) . " -->";
        }
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
                timerProgressBar: false,
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
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Require admin access or redirect
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        setFlashMessage('Please login first.', 'warning');
        redirect(baseUrl() . '/login.php');
    }
    if (!isAdmin()) {
        setFlashMessage('Access denied. Admin privileges required.', 'danger');
        redirect(baseUrl() . '/index.php');
    }
}

/**
 * Render a status badge
 * @param string $status
 * @param string $type success, warning, danger, info
 * @return string
 */
function renderStatusBadge(string $status, string $type = 'info'): string
{
    return "<span class=\"badge bg-{$type} rounded-pill\">" . e(ucfirst($status)) . "</span>";
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
    
    // Get the script's directory relative to the host
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // If we're inside the 'admin' subfolder, move up to find the true project root
    if (strpos($scriptPath, '/admin') !== false) {
        $scriptPath = rtrim(str_replace('/admin', '', $scriptPath), '/');
    }
    
    // Ensure the path is correct even if project is at root /
    if ($scriptPath === '') $scriptPath = '/';
    
    return rtrim($protocol . $host . $scriptPath, '/');
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
    $coverImage = null;
    $extractedTitle = $title;
    $extractedAuthor = $author;

    // 1. Extract cover image and metadata based on input type
    if (is_object($book)) {
        if (method_exists($book, 'getCoverImage')) {
            $coverImage = $book->getCoverImage();
        } elseif (isset($book->cover_image)) {
            $coverImage = $book->cover_image;
        }
        $extractedTitle = method_exists($book, 'getTitle') ? $book->getTitle() : ($book->title ?? $extractedTitle);
        $extractedAuthor = method_exists($book, 'getAuthor') ? $book->getAuthor() : ($book->author ?? $extractedAuthor);
    } elseif (is_array($book)) {
        $coverImage = $book['cover_image'] ?? null;
        $extractedTitle = $book['title'] ?? $extractedTitle;
        $extractedAuthor = $book['author'] ?? $extractedAuthor;
    } elseif (is_string($book)) {
        $coverImage = $book;
    }

    // 2. Resolve the cover image path/URL
    if ($coverImage) {
        // If it's already a full URL
        if (strpos($coverImage, 'http://') === 0 || strpos($coverImage, 'https://') === 0) {
            return $coverImage;
        }
        
        // If it's a local file
        $localPath = APP_ROOT . '/public/uploads/covers/' . $coverImage;
        if (file_exists($localPath)) {
            return baseUrl() . '/public/uploads/covers/' . $coverImage;
        }
    }
    
    // 3. Fallback to dummy cover
    return getDummyBookCover($extractedTitle, $extractedAuthor);
}

/**
 * Get a setting value from the database
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting(string $key, $default = null)
{
    static $settings = null;

    if ($settings === null) {
        try {
            $library = new \App\Library();
            $pdo = $library->getPdo();
            $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settings = [];
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Set a setting value in the database
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function setSetting(string $key, $value): bool
{
    try {
        $library = new \App\Library();
        $pdo = $library->getPdo();
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

// Check for Maintenance Mode
if (getSetting('maintenance_mode') === '1' && !isAdmin()) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    // Pages that are EXEMPT from maintenance mode redirect
    $exemptPages = ['maintenance.php', 'login.php', 'register.php', 'contact_us.php'];
    
    // Check if we are in admin directory or on an exempt page
    $isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
    
    if (!in_array($currentFile, $exemptPages) && !$isAdminDir) {
        header("Location: " . baseUrl() . "/maintenance.php");
        exit();
    }
}
