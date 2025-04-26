<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Focus Scroll</title>
</head>
<body>
    <div style="height:700px;"></div>  <div style="height:700px;"></div>
    <input type="search" id="is-search-input-18570" name="s" value="" placeholder="Enter your city and start exploring" autocomplete="off" onkeydown="return event.key != 'Enter'" class="is-search-input lazyloaded">
  <div style="height:700px;"></div>  <div style="height:700px;"></div>
    <script>
        document.getElementById('is-search-input-18570').addEventListener('focus', function() {
            this.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });
    </script>
</body>
</html>