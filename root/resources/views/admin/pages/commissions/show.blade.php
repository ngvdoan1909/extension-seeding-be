@extends('admin.layout.master')

@section('title')
    Sửa nhiệm vụ
@endsection

@section('style-libs')
@endsection

@section('content')
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h2>Sửa Nhiệm Vụ</h2>
        </div>

        <form method="POST" action="{{ route('admin.commissions.update', $commission->commission_id) }}" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PATCH')
            
            <div class="mb-4 card">
                <div class="card-header">
                    <h5>Thông tin website</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="text" class="form-control" value="{{ $commission->website->name }}" readonly>
                    </div>
                </div>
            </div>

            <div class="commission-item card mb-4">
                <div class="card-header">
                    <h5>Nhiệm vụ</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Từ khóa *</label>
                                <input type="text" name="key_word" class="form-control"
                                    maxlength="100" value="{{ old('key_word', $commission->key_word) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL *</label>
                                <input type="url" name="url" class="form-control" 
                                    value="{{ old('url', $commission->url) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Giới hạn/ngày *</label>
                                <input type="number" name="daily_limit" class="form-control" min="0"
                                    value="{{ old('daily_limit', $commission->daily_limit) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ảnh hướng dẫn hiện tại</label>
                        @if($commission->images->count() > 0)
                            <div class="row">
                                @foreach($commission->images as $image)
                                    <div class="col-md-2 mb-2">
                                        <img src="{{ \Storage::disk('minio')->url($image->image) }}" 
                                             class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">Không có ảnh hướng dẫn</p>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ảnh hướng dẫn mới (Thay thế ảnh cũ)</label>
                        <input type="file" name="image[]" class="form-control" multiple>
                        <small class="text-muted">Định dạng: PNG, JPG, JPEG, WEBP</small>
                    </div>

                    @if($commission->key_word_image)
                        <div class="mb-3">
                            <label class="form-label">Ảnh từ khóa hiện tại</label>
                            <div>
                                <img src="{{ \Storage::disk('minio')->url($commission->key_word_image) }}" 
                                     class="img-thumbnail" style="max-height: 100px;">
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.commissions.index') }}" class="btn btn-secondary">Quay lại</a>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
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
@endsection