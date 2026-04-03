@section('headerfiles')

    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>{{ env('APP_TITLE') }} {{ isset($page_name) ? ' | '.$page_name : '' }}</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="{{url('public/assets/img/favicon.ico?656')}}" rel="icon">
    <link href="{{url('public/assets/img/apple-touch-icon.png')}}" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{url('public/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/font-awesome/css/all.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/quill/quill.snow.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/quill/quill.bubble.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/remixicon/remixicon.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/notifIt/notifIt.min.css')}}" rel="stylesheet">
    <link href="{{url('public/assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{url('public/assets/css/style.css')}}" rel="stylesheet">
    <style>
        :root{
            --primary: {{ env('APP_THEME_COLOR', '#4154f1') }};
            --hov-primary: {{ env('APP_HOVER_THEME_COLOR', '#012970') }};
        }
    </style>
    @yield('pagewisestyle')
    <!-- =======================================================
    * Template Name: NiceAdmin - v2.5.0
    * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
    * Author: BootstrapMade.com
    * License: https://bootstrapmade.com/license/
    ======================================================== -->
@endsection