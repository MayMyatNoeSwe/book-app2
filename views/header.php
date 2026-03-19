<?php
// views/header.php
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Book Library System' ?> - My Library</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Animate.css for SweetAlert2 animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <link href="<?= baseUrl() ?>/public/css/custom.css" rel="stylesheet">
    <link href="<?= baseUrl() ?>/public/css/premium_new.css" rel="stylesheet">

    <!-- Favicon (optional - add your own) -->
    <!-- <link rel="icon" href="<?= baseUrl() ?>/public/images/favicon.ico"> -->

    <style>
        .cover-img {
            object-fit: cover;
            width: 80px;
            height: 120px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container mt-4">