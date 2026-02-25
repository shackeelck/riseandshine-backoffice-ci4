<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
    <link rel="icon" href="<?= base_url('assets/fav.png') ?>" type="image/png">
</head>
<body class="bg-gray-100  min-h-screen">
   
    <div class="flex   justify-center"> 
        <div class="flex justify-center mb-3">
          <img src="<?= base_url('assets/images/logo.png') ?>" alt="Rise And Shine" class=" w-auto" style="height: 100px;">
        </div>
        
    </div>
    
    <div class="flex items-center justify-center "> 
        
        <div class="w-full max-w-sm bg-white p-8 rounded-lg shadow-lg">
            <!-- Logo -->


            <h2 class="text-2xl font-semibold text-gray-700 text-center mb-6">Sign In</h2>


            <form action="/auth/login" method="post">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-600">Email</label>
                    <input type="text" id="username" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-600">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="inline-flex items-center text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="form-checkbox text-blue-500">
                        <span class="ml-2">Remember me</span>
                    </label>
                    <a href="/forgot-password" class="text-sm text-blue-500 hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="w-full py-2 px-4 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Login</button>
            </form>

            <?php /*?><p class="mt-4 text-center text-sm text-gray-600">
                Don't have an account? <a href="/register" class="text-blue-500 hover:underline">Sign up</a>
            </p><?php */?>
        </div>
    </div>
 <script type="module" src="<?= base_url('main.js') ?>"></script>
</body>
</html>




<?php /*?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 CDN -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="icon" href="<?= base_url('fav-icon-round.png') ?>" type="image/png">

</head>
<body>

    <div class="container mt-5" style="max-width: 400px;">
        <div class="card mx-auto mt-5" style="max-width: 400px;">
            <div class="card-header">Login</div>
            <div class="card-body">


                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                <?php endif; ?>
                <form action="/auth/login" method="post">
                    <div class="mb-3">
                        <label  class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required />
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
    </body>
</html>
<?php */?>