@livewireScripts(['url' => request()->getBaseUrl() . '/livewire/livewire.js'])
<script>
    // Keep Livewire's update endpoint inside the application's installation directory.
    document.querySelector('script[data-update-uri]').setAttribute('data-update-uri', @js(route('livewire.update')));
</script>
