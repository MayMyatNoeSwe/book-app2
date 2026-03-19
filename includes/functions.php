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
    return [
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
    $query = urlencode(trim($title . ' ' . $author . ' book cover'));
    $size = (int)$width . 'x' . (int)$height;
    $sig = abs(crc32($title . '|' . $author));
    return "https://source.unsplash.com/featured/" . $size . "?" . $query . "&sig=" . $sig;
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
