<div id="app">
<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>


    
    <div class="p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
      <?php 
      $modules = [
        ['calendar', 'Reservation Calendar'],
        ['collection', 'Room Types'],
        ['cube', 'Rooms'],
        ['users', 'Guest'],
        ['user-circle', 'Operator'],
        ['receipt-tax', 'Invoice'],
        ['credit-card', 'Payments'],
        ['cog', 'Settings'],
        ['chart-bar', 'Reports'],
      ];
      foreach ($modules as [$icon, $label]):
      ?>
        <a href="<?= base_url(strtolower(str_replace(' ', '-', $label))) ?>" 
           class="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow hover:bg-blue-50 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <use href="#<?= $icon ?>" />
          </svg>
          <span class="mt-1 text-sm font-medium text-gray-700"><?= $label ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    
    
</div>