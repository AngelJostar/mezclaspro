@livewireScripts(['url' => request()->getBaseUrl() . '/livewire/livewire.js'])
<script>
    // Keep the update endpoint inside the application's installation directory.
    document.querySelector('script[data-update-uri]').setAttribute('data-update-uri', @js(request()->getBaseUrl() . app('livewire')->getUpdateUri()));
</script>
