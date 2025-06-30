@extends('admin.layout.master')

@section('title')
    Thêm mới nhiệm vụ
@endsection

@section('style-libs')
@endsection

@section('content')
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h2>Thêm Nhiệm Vụ Mới</h2>
            <button type="button" id="add-commission" class="btn btn-secondary">Thêm nhiệm vụ</button>
        </div>

        <form method="POST" action="{{ route('admin.commissions.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="mb-4 card">
                <div class="card-header">
                    <h5>Thông tin chung</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Website *</label>
                        <select name="website_id" class="form-select" required>
                            <option value="">-- Chọn --</option>
                            @foreach($websites as $website)
                                <option value="{{ $website->website_id }}">{{ $website->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div id="commissions-container">
                <div class="commission-item card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Nhiệm vụ 1</h5>
                        <button type="button" class="btn btn-sm btn-danger remove-commission">Xóa</button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Từ khóa *</label>
                                    <input type="text" name="commissions[0][key_word]" class="form-control"
                                        maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">URL *</label>
                                    <input type="url" name="commissions[0][url]" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Giới hạn/ngày *</label>
                                    <input type="number" name="commissions[0][daily_limit]" class="form-control" min="0"
                                        value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh hướng dẫn</label>
                            <input type="file" name="commissions[0][image][]" class="form-control" multiple>
                            <small class="text-muted">Định dạng: PNG, JPG, JPEG, WEBP</small>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%">Lưu</button>
        </form>
    </div>
@endsection

@section('script-libs')
    <!-- prismjs plugin -->
    <script src="assets/libs/prismjs/prism.js"></script>

    <!-- notifications init -->
    <script src="{{ asset('theme/admin/assets/js/pages/notifications.init.js') }}"></script>
@endsection

@section('scripts')
    <script>
        let commissionCount = 1;

        document.getElementById('add-commission').addEventListener('click', function () {
            commissionCount++;
            const newItem = document.createElement('div');
            newItem.className = 'commission-item card mb-4';
            newItem.innerHTML = `
                    <div class="card-header d-flex justify-content-between">
                        <h5>Nhiệm vụ ${commissionCount}</h5>
                        <button type="button" class="btn btn-sm btn-danger remove-commission">Xóa</button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Từ khóa *</label>
                                    <input type="text" name="commissions[${commissionCount - 1}][key_word]" class="form-control" maxlength="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">URL *</label>
                                    <input type="url" name="commissions[${commissionCount - 1}][url]" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Giới hạn/ngày *</label>
                                    <input type="number" name="commissions[${commissionCount - 1}][daily_limit]" class="form-control" min="0" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh hướng dẫn</label>
                            <input type="file" name="commissions[${commissionCount - 1}][image][]" class="form-control" multiple>
                            <small class="text-muted">Định dạng: PNG, JPG, JPEG, WEBP</small>
                        </div>
                    </div>
                `;

            document.getElementById('commissions-container').appendChild(newItem);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-commission')) {
                if (document.querySelectorAll('.commission-item').length > 1) {
                    e.target.closest('.commission-item').remove();
                } else {
                    alert('Phải có ít nhất một nhiệm vụ');
                }
            }
        });
    </script>
@endsection