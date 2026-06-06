<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnyChat Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Add h-screen and overflow-hidden to the body to lock the viewport --}}
<body class="bg-slate-50 h-screen w-full overflow-hidden m-0 p-0">
    
    {{-- Render the dashboard directly without the centering flex wrapper --}}
    @livewire('anychat-dashboard')

    <livewire:anychat-widget 
    height="500px" 
    width="400px"
    variant="outline" 
    primaryColor="#7c3aed"
    adminColor="#f3f4f6"
    
    
 :config=$config />
/>

    @livewireScripts
</body>
</html>