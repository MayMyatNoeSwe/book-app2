<?php
// Download sample author photos
echo "<h2>Downloading Sample Author Photos</h2>";

// Create directory if it doesn't exist
$uploadDir = __DIR__ . '/public/uploads/authors/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "✅ Created authors directory<br>";
}

// Sample author photos from placeholder services
$authorPhotos = [
    'jk_rowling.jpg' => 'https://images.unsplash.com/photo-1494790108755-2616c6d4e6e8?w=300&h=300&fit=crop&crop=face',
    'tolkien.jpg' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop&crop=face',
    'orwell.jpg' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face',
    'austen.jpg' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=300&h=300&fit=crop&crop=face',
    'fitzgerald.jpg' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&h=300&fit=crop&crop=face',
    'harper_lee.jpg' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=300&h=300&fit=crop&crop=face',
    'herbert.jpg' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=300&h=300&fit=crop&crop=face',
    'crockford.jpg' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=300&h=300&fit=crop&crop=face',
    'martin.jpg' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=300&h=300&fit=crop&crop=face',
    'salinger.jpg' => 'https://images.unsplash.com/photo-1507591064344-4c6ce005b128?w=300&h=300&fit=crop&crop=face',
];

foreach ($authorPhotos as $filename => $url) {
    $filePath = $uploadDir . $filename;
    
    if (!file_exists($filePath)) {
        try {
            $imageData = @file_get_contents($url);
            if ($imageData !== false) {
                file_put_contents($filePath, $imageData);
                echo "✅ Downloaded: $filename<br>";
            } else {
                echo "❌ Failed to download: $filename<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error downloading $filename: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "⏭️ Already exists: $filename<br>";
    }
}

echo "<h3>✅ Author photos download completed!</h3>";
echo "<p><strong>Note:</strong> If some downloads failed, the system will use dummy images automatically.</p>";
echo "<p><a href='index.php'>View Home Page</a></p>";
?>