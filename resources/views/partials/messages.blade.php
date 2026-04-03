@if(session()->has('message'))
    <div class="alert alert-{{session()->get('icon')}} alert-dismissible fade show mt-3" role="alert">
        <i class="bi {{ session()->get('icon')=='success' ? 'bi-check-circle' : 'bi-exclamation-octagon'}} me-1"></i>
        {{ session()->get('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif