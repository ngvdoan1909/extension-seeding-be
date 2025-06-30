@extends('admin.layout.master')

@section('title')
    Danh sách người dùng
@endsection

@section('style-libs')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
@endsection

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý Người dùng</h2>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="ri-user-add-line"></i> Thêm mới
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="example" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th width="10px">Quản lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $key => $user)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->role == \App\Enums\RoleEnum::ADMIN->value)
                                        <span class="badge bg-danger">Admin</span>
                                    @else
                                        <span class="badge bg-success">User</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.show', $user->user_id) }}" class="btn btn-sm btn-warning"
                                        title="Sửa">
                                        <i class="ri-edit-line"></i>
                                    </a>

                                    <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger mt-2" title="Xóa"
                                            onclick="return confirm('Bạn có chắc không?')">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div style="position: fixed; bottom: 1rem; right: 1rem; z-index: 999;">
            <div id="borderedToast2" class="toast toast-border-success overflow-hidden mt-3 fade show" role="alert"
                aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                <div class="toast-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-2">
                            <i class="ri-checkbox-circle-fill align-middle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0"> {{ session('success') }} </h6>
                        </div>
                        <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div style="position: fixed; bottom: 1rem; right: 1rem; z-index: 999;">
            <div id="borderedToast2" class="toast toast-border-error overflow-hidden mt-3 fade show" role="alert"
                aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
                <div class="toast-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-2">
                            <i class="ri-checkbox-circle-fill align-middle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0"> {{ session('error') }} </h6>
                        </div>
                        <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script-libs')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!--datatable js-->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

    <script src="{{ asset('theme/admin/assets/js/pages/datatables.init.js') }}"></script>

    <!-- notifications init -->
    <script src="{{ asset('theme/admin/assets/js/pages/notifications.init.js') }}"></script>
@endsection

@section('scripts')
    <script>
        new DataTable("#example", {
            order: [
                [0, 'desc']
            ]
        })
    </script>
@endsection