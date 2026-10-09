<div>
  <h1>Users</h1>

  @if(session('status'))
    <div style="border:1px solid #15803d;color:#15803d;border-radius:8px;padding:8px 12px;margin:12px 0;">{{ session('status') }}</div>
  @endif
  @if(session('error'))
    <div style="border:1px solid #b91c1c;color:#b91c1c;border-radius:8px;padding:8px 12px;margin:12px 0;">{{ session('error') }}</div>
  @endif

  <div style="display:flex;gap:8px;margin:12px 0;">
    <input type="text" wire:model.live="search" placeholder="Search username, name, email, role..." style="flex:1;padding:8px;border:1px solid #ccc;border-radius:8px;">
    <button wire:click="create" style="padding:8px 16px;border:1px solid #1f3a8a;background:#1f3a8a;color:#fff;border-radius:8px;">New user</button>
  </div>

  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr style="text-align:left;border-bottom:2px solid #ddd;">
        <th style="padding:8px;">Username</th>
        <th style="padding:8px;">Name</th>
        <th style="padding:8px;">Email</th>
        <th style="padding:8px;">Role</th>
        <th style="padding:8px;">Active</th>
        <th style="padding:8px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($users as $user)
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:8px;">{{ $user->username }}</td>
          <td style="padding:8px;">{{ $user->name }}</td>
          <td style="padding:8px;">{{ $user->email ?? '-' }}</td>
          <td style="padding:8px;">{{ $roles[$user->role] ?? $user->role }}</td>
          <td style="padding:8px;">{{ $user->active ? 'Yes' : 'No' }}</td>
          <td style="padding:8px;white-space:nowrap;">
            <button wire:click="edit({{ $user->id }})">Edit</button>
            <button wire:click="toggleActive({{ $user->id }})">{{ $user->active ? 'Disable' : 'Enable' }}</button>
            <button wire:click="openPasswordReset({{ $user->id }})">Reset password</button>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="padding:16px;color:#666;">No users found.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:12px;">{{ $users->links() }}</div>

  @if($showModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;">
      <div style="background:#fff;border-radius:12px;padding:20px;min-width:320px;max-width:480px;width:90%;">
        <h2>{{ $editingId ? 'Edit user' : 'New user' }}</h2>
        <div style="display:grid;gap:8px;margin-top:12px;">
          <label>Username <input type="text" wire:model="username" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;"></label>
          @error('username')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
          <label>Name <input type="text" wire:model="name" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;"></label>
          @error('name')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
          <label>Email (optional) <input type="email" wire:model="email" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;"></label>
          @error('email')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
          <label>Role
            <select wire:model="role" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;">
              @foreach($roles as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </label>
          @error('role')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
          @if($editingId)
            <label><input type="checkbox" wire:model="active"> Active</label>
            @error('active')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
          @endif
          <label>{{ $editingId ? 'New password (leave blank to keep)' : 'Password (min 6)' }} <input type="password" wire:model="password" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;"></label>
          @error('password')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
          <button wire:click="closeModal">Cancel</button>
          <button wire:click="save" style="padding:8px 16px;border:1px solid #1f3a8a;background:#1f3a8a;color:#fff;border-radius:8px;">Save</button>
        </div>
      </div>
    </div>
  @endif

  @if($showPasswordModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;">
      <div style="background:#fff;border-radius:12px;padding:20px;min-width:320px;max-width:420px;width:90%;">
        <h2>Reset password</h2>
        <div style="display:grid;gap:8px;margin-top:12px;">
          <label>New password (min 6) <input type="password" wire:model="newPassword" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:8px;"></label>
          @error('newPassword')<span style="color:#b91c1c;font-size:12px;">{{ $message }}</span>@enderror
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
          <button wire:click="closeModal">Cancel</button>
          <button wire:click="resetPassword" style="padding:8px 16px;border:1px solid #1f3a8a;background:#1f3a8a;color:#fff;border-radius:8px;">Reset</button>
        </div>
      </div>
    </div>
  @endif
</div>
