<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
  
 
  <link rel="icon" href="<?= base_url('assets/fav.png') ?>" type="image/png">
  <link rel="preload" href="<?= base_url('hero-outline.svg') ?>" as="image">
    
    <svg style="display:none">
      <!-- Example modules -->
      <symbol id="heroicon-home" viewBox="0 0 24 24">
        <!-- Home icon path -->
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-7 9 7v11a2 2 0 0 1-2 2h-4a2 2 0…"/>
      </symbol>
      <symbol id="heroicon-collection" viewBox="0 0 24 24">
        <path d="..."/>
      </symbol>
      <!-- Continue for cube, calendar, users, user-circle, receipt-tax, credit-card, cog, chart-bar -->
    </svg>

  
    
</head>
<body>
    
    
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand text-white ps-3" href="/dashboard"><?php /*?><img src="<?=base_url('assets/images/logo.png');?>" style="width:100px;" ><?php */?></a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if (session()->get('role') !== 'deliver') : ?>
         <li class="nav-item">
          <a class="nav-link" href="/orders/add"><i class="fas fa-cart-plus"></i> Add Order</a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link" href="/orders/all"><i class="fas fa-shopping-cart"></i> All Orders</a>
        </li>

        <?php if (session()->get('role') === 'admin') : ?>
        <li class="nav-item">
          <a class="nav-link" href="/users"><i class="fas fa-users-cog"></i> Manage Users</a>
        </li>
        <?php endif; ?>
        
       
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0"> 
         <li class="nav-item">
          <a class="nav-link" href="/auth/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

    

<div class="container mt-4">
    
