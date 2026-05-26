<!-- packages/SaamMi/AnyChat/resources/views/panel-master.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AnyChat Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        {{-- This single line renders your widget with the panel's unique settings --}}

  <livewire:anychat-widget :config="$config"
      height="500px" 
    width="400px" 
    variant="outline" 
    primaryColor="#7c3aed"
    adminColor="red"
    />
       
 @livewire('anychat-dashboard')
    </div>
    @livewireScripts
</body>
</html>