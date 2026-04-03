<!DOCTYPE html>
<html lang="{{app()->getLocale()}}">
    <head>
        @yield('headerfiles')
    </head>
    <body>
        @yield('content')
        @yield('footerfiles')
    </body>
</html>
