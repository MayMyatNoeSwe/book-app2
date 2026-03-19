# Author Photos Directory

This directory contains author profile photos for the library system.

## How it works:

1. **Real Photos**: If an author has a photo file in this directory, it will be displayed
2. **Dummy Images**: If no photo exists, the system automatically generates a dummy image using:
   - Primary: UI Avatars service with author's name
   - Fallback: Placeholder image with author's initials

## Adding Author Photos:

1. Save author photos in this directory with the filename specified in the database
2. Recommended format: JPG, PNG (120x120px or larger, square aspect ratio works best)
3. Use descriptive filenames like: `author_name.jpg`

## Automatic Fallbacks:

The system provides multiple fallback options:
- UI Avatars API (generates colorful avatars with names)
- Placeholder service with initials
- Graceful degradation ensures the page always works

## Sample Photos:

Run `download_author_photos.php` from the root directory to download sample photos for testing.