@section('footerfiles')    
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="{{url('public/assets/vendor/js/vendor.bundle.base.js')}}"></script>
    <script src="{{url('public/assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
    <script src="{{url('public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{url('public/assets/vendor/quill/quill.min.js')}}"></script>
    <script src="{{url('public/assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
    <script src="{{url('public/assets/vendor/tinymce/tinymce.min.js')}}"></script>
    <script src="{{url('public/assets/vendor/notifIt/notifIt.min.js')}}"></script>
    @yield('pagewisescript')
    <script>
        function NotifMsg(message, icon, type){
            notif({
                msg: "<b>"+type+"</b> : "+message,
                type: icon,
                position: "right",
                // width:'all',
                // height: 100,
                autohide: false
            });
        }
        $(document).ready(function(e){
            var icon = "{{Session::get('icon', 'success')}}", message = "{{Session::get('message')}}", type = "{{ucfirst(Session::get('icon', 'success'))}}";
            if(message!=""){
                NotifMsg(message, icon, type);
            }
        });

        function showConfirmToast(url){
            $('#confirm_url').attr('href', url);
            $('#confirmModal').modal('show');
        }
    </script>
    <!-- Template Main JS File -->
    <script src="{{url('public/assets/js/main.js')}}"></script>
    @yield('customjs')
@endsection