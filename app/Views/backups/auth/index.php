<?php /*?><!DOCTYPE html><html><head><meta charset="UTF-8">
<link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
<title>CI4 + Vue + Tailwind</title></head>
<body>
  <div id="app">this is a test</div>
  <script type="module" src="<?= base_url('main.js') ?>"></script>
</body></html><?php */?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-sm">
        <h2 class="text-2xl font-semibold text-center text-gray-700 mb-6">Sign In</h2>

        <form action="/auth" method="post">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-600">Email</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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

        <p class="mt-4 text-center text-sm text-gray-600">
            Don't have an account? <a href="/register" class="text-blue-500 hover:underline">Sign up</a>
        </p>
    </div>
 <script type="module" src="<?= base_url('main.js') ?>"></script>
</body>
</html>
