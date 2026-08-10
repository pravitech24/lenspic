@extends('settings.layout')
@section('title','Watermark')
@section('settings-content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.3rem;font-weight:800;">Watermark Settings</h1>
  <button form="wtForm" type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
</div>
<div class="card">
  <div class="card-body" style="padding:1.75rem;">
    <form id="wtForm" action="{{ route('settings.watermark.update') }}" method="POST">
      @csrf
      <div class="form-group">
        <label>Watermark Text</label>
        <input type="text" name="watermark_text" value="{{ old('watermark_text', $user->meta['watermark_text'] ?? '') }}" placeholder="© Your Studio Name 2024">
        <p style="font-size:12px;color:#94a3b8;margin-top:.35rem;">This text will be overlaid on all downloaded photos in groups where watermark is enabled.</p>
      </div>
      <div class="form-group">
        <label>Position</label>
        <select name="watermark_position">
          @foreach(['bottom-right'=>'Bottom Right','bottom-left'=>'Bottom Left','bottom-center'=>'Bottom Center','top-right'=>'Top Right','top-left'=>'Top Left','center'=>'Center'] as $v=>$l)
          <option value="{{ $v }}" {{ ($user->meta['watermark_position'] ?? 'bottom-right') === $v ? 'selected' : '' }}>{{ $l }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>Opacity: <span id="opacityVal">{{ $user->meta['watermark_opacity'] ?? 70 }}%</span></label>
        <input type="range" name="watermark_opacity" min="10" max="100" value="{{ $user->meta['watermark_opacity'] ?? 70 }}" style="width:100%;accent-color:#6366f1;" oninput="document.getElementById('opacityVal').textContent=this.value+'%'">
      </div>
      <!-- Live Preview -->
      <div style="background:#f1f5f9;border-radius:12px;height:180px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#667eea,#764ba2);">
        <div style="position:absolute;bottom:12px;right:16px;color:rgba(255,255,255,.8);font-size:13px;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,.5);" id="wtPreview">
          {{ $user->meta['watermark_text'] ?? '© Your Studio Name' }}
        </div>
        <span style="color:rgba(255,255,255,.4);font-size:13px;">Photo Preview</span>
      </div>
    </form>
  </div>
</div>
@push('scripts')
<script>
document.querySelector('[name=watermark_text]').addEventListener('input', function(){
  document.getElementById('wtPreview').textContent = this.value || '© Your Studio Name';
});
</script>
@endpush
@endsection
