<div>
  <h1>Settings</h1>

  @if(session('status'))
    <div style="border:1px solid #15803d;color:#15803d;border-radius:8px;padding:8px 12px;margin:12px 0;">{{ session('status') }}</div>
  @endif

  <form wire:submit="save" style="display:grid;gap:12px;max-width:720px;margin-top:12px;">
    <label>Company
      <input type="text" wire:model="company" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;">
    </label>
    @error('company')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror

    <label>Currency
      <input type="text" wire:model="currency" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;">
    </label>
    @error('currency')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror

    <h2 style="margin-top:8px;">Transition permissions</h2>
    @foreach($transitions as $key => $transition)
      <fieldset style="border:1px solid #ddd;border-radius:8px;padding:12px;">
        <legend style="padding:0 6px;"><strong>{{ $transition['label'] }}</strong> <span style="color:#666;">({{ $key }})</span></legend>
        <div style="font-size:13px;color:#444;margin-bottom:4px;">Who may perform:</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;margin-bottom:8px;">
          @foreach($performerRoles as $roleKey => $roleLabel)
            <label style="font-size:13px;"><input type="checkbox" wire:model="perm.{{ $key }}.roles" value="{{ $roleKey }}"> {{ $roleLabel }}</label>
          @endforeach
        </div>
        <div style="font-size:13px;color:#444;margin-bottom:4px;">Notify:</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px 16px;">
          @foreach($notifyRoles as $roleKey => $roleLabel)
            <label style="font-size:13px;"><input type="checkbox" wire:model="perm.{{ $key }}.notify" value="{{ $roleKey }}"> {{ $roleLabel }}</label>
          @endforeach
        </div>
      </fieldset>
    @endforeach

    <div>
      <button type="submit" style="padding:8px 16px;border:1px solid #1f3a8a;background:#1f3a8a;color:#fff;border-radius:8px;">Save settings</button>
    </div>
  </form>
</div>
