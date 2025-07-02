@extends('admin.layout.master')

@section('title')
    Thêm mới website
@endsection

@section('style-libs')
@endsection

@section('content')
    <form class="form-group" action="{{ route('admin.websites.store') }}" method="POST" novalidate enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Thêm mới website</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="live-preview">
                            <div class="row gy-4">
                                <!-- Thông tin website -->
                                <div class="col-md-6">
                                    <h4 class="mb-3">Thông tin</h4>
                                    <div>
                                        <label for="name" class="form-label">Tên</label>
                                        <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                                        @error('name')
                                            <p class="mt-2 text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="mt-3">
                                        <label for="domain" class="form-label">Tên miền</label>
                                        <input type="text" class="form-control" id="domain" name="domain" required value="{{ old('domain') }}">
                                        @error('domain')
                                            <p class="mt-2 text-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6" id="commission-container">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="mb-0">Nhiệm vụ của trang web</h4>
                                        <button type="button" class="btn btn-sm btn-success" id="add-commission">
                                            <i class="ri-add-line"></i> Thêm nhiệm vụ
                                        </button>
                                    </div>

                                    @php
                                        $oldCommissions = old('commissions', [['key_word' => '', 'url' => '', 'daily_limit' => '']]);
                                    @endphp
                                    
                                    @foreach($oldCommissions as $index => $commission)
                                    <div class="commission-item card mb-3">
                                        <div class="card-body">
                                            @if($index > 0)
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-commission">
                                                    <i class="ri-delete-bin-line"></i> Xóa
                                                </button>
                                            </div>
                                            @endif
                                            <div class="mt-2">
                                                <label class="form-label">Từ khóa tìm kiếm</label>
                                                <input type="text" class="form-control" 
                                                       name="commissions[{{ $index }}][key_word]" 
                                                       value="{{ $commission['key_word'] }}" required>
                                                @error("commissions.$index.key_word")
                                                    <p class="mt-2 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">Địa chỉ trang web</label>
                                                <input type="text" class="form-control" 
                                                       name="commissions[{{ $index }}][url]" 
                                                       value="{{ $commission['url'] }}" required>
                                                @error("commissions.$index.url")
                                                    <p class="mt-2 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">Lượt truy cập cần</label>
                                                <input type="number" class="form-control" 
                                                       name="commissions[{{ $index }}][daily_limit]" 
                                                       value="{{ $commission['daily_limit'] }}" required min="0">
                                                @error("commissions.$index.daily_limit")
                                                    <p class="mt-2 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label">Hình ảnh hướng dẫn</label>
                                                <input type="file" class="form-control" 
                                                       name="commissions[{{ $index }}][images][]" multiple>
                                                @error("commissions.$index.images")
                                                    <p class="mt-2 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary" style="width: 100%">Lưu</button>
            </div>
        </div>
    </form>

    <!-- Template commission (đặt bên ngoài form) -->
    <div id="commission-template" style="display: none;">
        <div class="commission-item card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-danger remove-commission">
                        <i class="ri-delete-bin-line"></i> Xóa
                    </button>
                </div>
                <div class="mt-2">
                    <label class="form-label">Từ khóa tìm kiếm</label>
                    <input type="text" class="form-control" name="commissions[__INDEX__][key_word]" required>
                </div>
                <div class="mt-3">
                    <label class="form-label">Địa chỉ trang web</label>
                    <input type="text" class="form-control" name="commissions[__INDEX__][url]" required>
                </div>
                <div class="mt-3">
                    <label class="form-label">Lượt truy cập cần</label>
                    <input type="number" class="form-control" name="commissions[__INDEX__][daily_limit]" required min="0">
                </div>
                <div class="mt-3">
                    <label class="form-label">Hình ảnh hướng dẫn</label>
                    <input type="file" class="form-control" name="commissions[__INDEX__][images][]" multiple>
                </div>
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
    <!-- prismjs plugin -->
    <script src="assets/libs/prismjs/prism.js"></script>

    <!-- notifications init -->
    <script src="{{ asset('theme/admin/assets/js/pages/notifications.init.js') }}"></script>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const commissionContainer = document.getElementById('commission-container');
        const addButton = document.getElementById('add-commission');
        const template = document.getElementById('commission-template');
        
        let commissionCount = document.querySelectorAll('#commission-container .commission-item').length;
        
        addButton.addEventListener('click', function() {
            const newCommission = template.cloneNode(true);
            newCommission.style.display = 'block';
            newCommission.removeAttribute('id');
            
            const html = newCommission.innerHTML.replace(/__INDEX__/g, commissionCount);
            newCommission.innerHTML = html;
            
            commissionContainer.appendChild(newCommission);
            commissionCount++;
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-commission')) {
                const commissionItem = e.target.closest('.commission-item');
                if (document.querySelectorAll('#commission-container .commission-item').length > 1) {
                    commissionItem.remove();
                    updateCommissionIndexes();
                } else {
                    alert('Phải có ít nhất một nhiệm vụ');
                }
            }
        });

        function updateCommissionIndexes() {
            const items = document.querySelectorAll('#commission-container .commission-item');
            commissionCount = 0;
            
            items.forEach((item, index) => {
                const inputs = item.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    const name = input.getAttribute('name')
                        .replace(/commissions\[\d+\]/g, `commissions[${index}]`);
                    input.setAttribute('name', name);
                });
            });
            
            commissionCount = items.length;
        }
    });
</script>
@endsection