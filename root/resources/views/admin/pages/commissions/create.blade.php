@extends('admin.layout.master')

@section('title')
    Thêm mới nhiệm vụ
@endsection

@section('content')
    <div class="container">
        <h2 class="mb-4">Thêm Nhiệm Vụ Mới</h2>

        <form method="POST" action="{{ route('admin.commissions.store') }}" enctype="multipart/form-data" novalidate
            id="commission-form">
            @csrf

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Thông tin chung</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Giới hạn/ngày *</label>
                                <input type="number" name="daily_limit" class="form-control" min="0" value="0" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Danh sách URL</h5>
                    <button type="button" id="add-url" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Thêm URL
                    </button>
                </div>
                <div class="card-body" id="urls-container">
                    <div class="url-item card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <h6 class="mb-0">URL #1</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-url">
                                Xóa
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">URL *</label>
                                        <input type="url" name="urls[0][url]" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Từ khóa *</label>
                                        <input type="text" name="urls[0][key_word]" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ảnh hướng dẫn</label>
                                <input type="file" name="urls[0][images][]" class="form-control" multiple>
                                <small class="text-muted">Có thể chọn nhiều ảnh</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Lưu nhiệm vụ</button>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let urlCount = 1;

            // Thêm URL mới
            document.getElementById('add-url').addEventListener('click', function (e) {
                e.preventDefault(); // Ngăn form submit
                urlCount++;
                const newItem = document.createElement('div');
                newItem.className = 'url-item card mb-3';
                newItem.innerHTML = `
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <h6 class="mb-0">URL #${urlCount}</h6>
                            <button type="button" class="btn btn-sm btn-danger remove-url">
                                Xóa
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">URL *</label>
                                        <input type="url" name="urls[${urlCount - 1}][url]" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Từ khóa *</label>
                                        <input type="text" name="urls[${urlCount - 1}][key_word]" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ảnh hướng dẫn</label>
                                <input type="file" name="urls[${urlCount - 1}][images][]" class="form-control" multiple>
                                <small class="text-muted">Có thể chọn nhiều ảnh</small>
                            </div>
                        </div>
                    `;
                document.getElementById('urls-container').appendChild(newItem);
            });

            // Xử lý nút xóa
            document.addEventListener('click', function (e) {
                if (e.target.closest('.remove-url')) {
                    e.preventDefault();
                    const urlItems = document.querySelectorAll('.url-item');
                    if (urlItems.length > 1) {
                        e.target.closest('.url-item').remove();
                        // Cập nhật lại index
                        document.querySelectorAll('.url-item').forEach((item, index) => {
                            item.querySelector('h6').textContent = `URL #${index + 1}`;
                            // Cập nhật lại name attribute
                            item.querySelectorAll('input').forEach(input => {
                                const name = input.getAttribute('name');
                                input.setAttribute('name', name.replace(/urls\[\d+\]/, `urls[${index}]`));
                            });
                        });
                    } else {
                        alert('Phải có ít nhất một URL');
                    }
                }
            });

            // Ngăn form submit khi nhấn Enter
            document.getElementById('commission-form').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
        });
    </script>
@endsection