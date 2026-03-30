<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnyChat Smoke Test</title>
    
    @livewireStyles

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.min.js"></script>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-10">
        <h1 class="text-2xl font-bold mb-4">Dusk is Working!</h1>

        @livewire('anychat-widget')

        <button dusk="test-button" class="mt-4 p-2 bg-blue-500 text-white" onclick="this.innerText='Button Clicked!'">
            Click Me
        </button>
    </div>

    @livewireScripts
</body>
</html>
