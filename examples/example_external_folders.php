<?php

/**
 * Example: Loading MediaFolders outside the Medias class
 * 
 * This example demonstrates how to manually create MediaFolder instances
 * and load them without using the loadMediaFolders() method.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Medias\Medias;
use JDZ\Medias\MediasFolder;
use JDZ\Medias\MediasList;

// Define the public path
$publicPath = __DIR__ . '/../public';

// Create the Medias instance
$medias = new Medias($publicPath);

// Get the MediasList instance
$mediaList = $medias->getMedialist();

// ============================================
// Manually create MediaFolders outside the class
// ============================================

$folders = [];

// 1. Create default media folder
$folders['media/'] = new MediasFolder('media/', 'media', [
    'width' => 1200,
    'height' => 1200,
]);

// 2. Add custom configured folders (if you have them)
$configFolders = [
    (object)[
        'name' => 'gallery',
        'path' => 'media/gallery/',
        'type' => 'media',
        'width' => 800,
        'height' => 600,
    ],
    (object)[
        'name' => 'products',
        'path' => 'media/products/',
        'type' => 'media',
        'width' => 1000,
        'height' => 1000,
    ],
];

foreach ($configFolders as $folder) {
    $extraData = (array)$folder;
    unset($extraData['name']);
    unset($extraData['path']);
    unset($extraData['type']);

    $folders['media/' . $folder->name . '/'] = new MediasFolder(
        'media/' . $folder->name . '/',
        'media',
        $extraData
    );
}

// 3. Discover and add media folders from filesystem
$mediaFolders = $mediaList->getMediaFolders($publicPath, 'media/');
foreach ($mediaFolders as $mediaFolder) {
    if (!isset($folders[$mediaFolder])) {
        $folders[$mediaFolder] = new MediasFolder($mediaFolder, 'media', [
            'width' => 1200,
            'height' => 1200,
        ]);
    }
}

// 4. Add fonts folder
$folders['fonts/'] = new MediasFolder('fonts/', 'fonts');

// 5. Add assets/images folder
$folders['assets/images/'] = new MediasFolder('assets/images/', 'assets', [
    'width' => 1200,
    'height' => 1200,
]);

// 6. Discover and add assets folders
$assetsFolders = $mediaList->getMediaFolders($publicPath, 'assets/images/');
foreach ($assetsFolders as $assetFolder) {
    if (!isset($folders[$assetFolder])) {
        $folders[$assetFolder] = new MediasFolder($assetFolder, 'assets', [
            'width' => 1200,
            'height' => 1200,
        ]);
    }
}

// 7. Discover and add user folders
$usersFolders = $mediaList->getMediaFolders($publicPath, 'users/');
foreach ($usersFolders as $usersFolder) {
    if (!isset($folders[$usersFolder])) {
        $folders[$usersFolder] = new MediasFolder($usersFolder, 'assets', [
            'width' => 1200,
            'height' => 1200,
        ]);
    }
}

// ============================================
// Now load the media files from the folders
// ============================================

$files = $medias->loadMediafiles($folders);

// ============================================
// Display results
// ============================================

echo "Total folders loaded: " . count($folders) . "\n";
echo "Total files found: " . count($files) . "\n\n";

echo "Folders:\n";
echo "--------\n";
foreach ($folders as $path => $folder) {
    $data = $folder->all();
    echo "- {$path} (type: {$data['type']})\n";
}

echo "\n";
echo "Sample files (first 10):\n";
echo "------------------------\n";
$count = 0;
foreach ($files as $path => $file) {
    echo "- {$file->folder}{$file->name} (type: {$file->type})\n";
    if (++$count >= 10) {
        break;
    }
}

if (count($files) > 10) {
    echo "... and " . (count($files) - 10) . " more files\n";
}
