<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $companyName = class_exists(\App\Models\Setting::class) ? \App\Models\Setting::where('key', 'company_name')->pluck('value')->first() : 'Noble Solar';
    
    $companyLogo = class_exists(\App\Models\Setting::class) ? \App\Models\Setting::where('key', 'company_logo')->pluck('value')->first() : asset('images/logo/logo.png');
    
    if (!filter_var($companyLogo, FILTER_VALIDATE_URL)) {
        $companyLogo = asset($companyLogo);
    }
    ?>
    <meta charset="UTF-8" />
    @if ($companyLogo)
        <link rel="icon" href="{{ asset($companyLogo) }}" type="image/png" />
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" />
    @endif
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $companyName }}</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
    @vite(['resources/js/main.js'])
    <meta name="theme-color" content="#7367F0" />
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}" />
    <!-- <link rel="manifest" href="/manifest.webmanifest" /> -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/images/logo/logo.png">
</head>

<body>
    <div id="app">
        <div id="loading-bg">
            <div class="loading-logo">
                <!-- Company Logo Instead of SVG -->
                @if ($companyLogo)
                    <img src="{{ $companyLogo }}" alt="{{ $companyName }}"
                        style="max-height: 100px; max-width: 180px;" />
                    <!-- SVG Logo -->
                @else
                    <svg width="86" height="48" viewBox="0 0 34 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M0.00183571 0.3125V7.59485C0.00183571 7.59485 -0.141502 9.88783 2.10473 11.8288L14.5469 23.6837L21.0172 23.6005L19.9794 10.8126L17.5261 7.93369L9.81536 0.3125H0.00183571Z"
                            fill="var(--initial-loader-color)" />
                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                            d="M8.17969 17.7762L13.3027 3.75173L17.589 8.02192L8.17969 17.7762Z" fill="#161616" />
                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                            d="M8.58203 17.2248L14.8129 5.24231L17.6211 8.05247L8.58203 17.2248Z" fill="#161616" />
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M8.25781 17.6914L25.1339 0.3125H33.9991V7.62657C33.9991 7.62657 33.8144 10.0645 32.5743 11.3686L21.0179 23.6875H14.5487L8.25781 17.6914Z"
                            fill="var(--initial-loader-color)" />
                    </svg>
                    /* The line `
                    <link rel="icon" href="{{ asset('favicon.ico') }}" />` is setting the favicon for the
                    website. The `favicon.ico` file is typically a small image file that represents the website and is
                    displayed in the browser tab or next to the URL in the address bar. In this case, the `asset()`
                    function is used to generate the URL for the favicon file, ensuring that the correct path is used
                    to access the favicon. */
                @endif
            </div>
            <div class="loading">
                <div class="effect-1 effects"></div>
                <div class="effect-2 effects"></div>
                <div class="effect-3 effects"></div>
            </div>
        </div>
    </div>

    <script>
        const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
        const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#7367F0'

        if (loaderColor)
            document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

        if (primaryColor)
            document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
    </script>
</body>

</html>
