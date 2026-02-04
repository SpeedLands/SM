<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin() && auth()->user()->isViewStaff(), 403);
    }

    public string $search = '';
    public string $roleFilter = 'Todos';
    public string $statusFilter = 'Todos';
    
    // User Creation/Editing
    public bool $showUserModal = false;
    public string $userId = '';
    public string $userName = '';
    public string $userEmail = '';
    public string $userRole = 'TEACHER';
    public string $userPhone = '';
    public string $userOccupation = '';
    public string $userPassword = '';
    public bool $showPassword = false;

    protected $rules = [
        'userName' => 'required|string|max:255',
        'userEmail' => 'required|email|max:255',
        'userRole' => 'required|in:ADMIN,TEACHER,PARENT',
        'userPhone' => 'nullable|string|max:20',
        'userOccupation' => 'nullable|string|max:100',
        // Password is required when creating a new user (userId empty).
        'userPassword' => 'required_without:userId|string|min:8',
    ];

    protected $messages = [
        'userEmail.unique' => 'Este correo electrónico ya está registrado en el sistema.',
    ];

    public function rules() 
    {
        $rules = $this->rules;
        
        $rules['userEmail'] = 'required|email|max:255|unique:users,email' . ($this->userId ? ',' . $this->userId : '');

        // If creating a new user, password is required
        if (!$this->userId) {
            $rules['userPassword'] = 'required|string|min:8';
        }

        return $rules;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter($status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['userId', 'userName', 'userEmail', 'userRole', 'userPhone', 'userOccupation', 'userPassword', 'showPassword']);
        $this->showUserModal = true;
    }

    public function editUser(User $user): void
    {
        $this->userId = $user->id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->userRole = $user->role;
        $this->userPhone = $user->phone ?? '';
        $this->userOccupation = $user->occupation ?? '';
        $this->userPassword = '';
        $this->showPassword = false;
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $this->validate();

        $transform = function($text) {
            if (!$text) return null;
            
            $text = mb_strtoupper($text, 'UTF-8');
            
            $accents = [
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                'Ü' => 'U', 'Ñ' => 'N', 'À' => 'A', 'È' => 'E', 'Ì' => 'I',
                'Ò' => 'O', 'Ù' => 'U'
            ];
            
            return strtr($text, $accents);
        };

        $cleanName = $transform($this->userName);
        $cleanOccupation = $this->userRole === 'PARENT' ? $transform($this->userOccupation) : null;

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $data = [
                'name' => $cleanName,
                'email' => $this->userEmail,
                'role' => $this->userRole,
                'phone' => $this->userRole === 'PARENT' ? $this->userPhone : null,
                'occupation' => $cleanOccupation,
            ];

            if ($this->userPassword) {
                $data['password'] = Hash::make($this->userPassword);
            }

            $user->update($data);
        } else {
            User::create([
                'id' => (string) Str::uuid(),
                'name' => $cleanName,
                'email' => $this->userEmail,
                'password' => Hash::make($this->userPassword),
                'role' => $this->userRole,
                'status' => 'FORCE_PASSWORD_CHANGE',
                'phone' => $this->userRole === 'PARENT' ? $this->userPhone : null,
                'occupation' => $cleanOccupation,
            ]);
        }

        $this->showUserModal = false;
        $this->dispatch('user-saved');
    }

    public function toggleBlock(User $user): void
    {
        if ($user->id === auth()->id()) {
            return;
        }

        $user->update([
            'status' => $user->status === 'BLOCKED' ? 'ACTIVE' : 'BLOCKED'
        ]);
    }

    public function resetPassword(User $user): void
    {
        $user->update([
            'status' => 'FORCE_PASSWORD_CHANGE'
        ]);
        $this->dispatch('password-reset-triggered');
    }

    public function with(): array
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->roleFilter !== 'Todos') {
            $query->where('role', $this->roleFilter);
        }

        if ($this->statusFilter !== 'Todos') {
            if ($this->statusFilter === 'Activos') {
                $query->where('status', 'ACTIVE');
            } elseif ($this->statusFilter === 'Bloqueados') {
                $query->where('status', 'BLOCKED');
            } elseif ($this->statusFilter === 'Pendiente Cambio Pass') {
                $query->where('status', 'FORCE_PASSWORD_CHANGE');
            }
        }

        return [
            'users' => $query->latest()->paginate(10),
            'totalUsersCount' => User::count(),
        ];
    }
}; ?>

<div class="space-y-6 text-zinc-900 dark:text-white">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Gestión de Usuarios y Roles</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Administre el acceso, asigne roles y gestione credenciales de seguridad. Los nuevos usuarios requerirán cambio de contraseña al primer inicio.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">Añadir Nuevo Usuario</flux:button>
    </div>

    <!-- Filters Section -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:field>
                <flux:label>Buscar usuario</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nombre o Correo electrónico..." />
            </flux:field>

            <flux:field>
                <flux:label>Filtrar por Rol</flux:label>
                <flux:select wire:model.live="roleFilter">
                    <option value="Todos">Todos los Roles</option>
                    <option value="ADMIN">Administrativo</option>
                    <option value="TEACHER">Maestro</option>
                    <option value="PARENT">Padre/Tutor</option>
                </flux:select>
            </flux:field>
        </div>

        {{-- Status filters hidden as requested --}}
        <div class="hidden flex-wrap items-center gap-2">
            <flux:text class="mr-2 text-sm font-medium">Estado:</flux:text>
            @foreach(['Todos', 'Activos', 'Bloqueados', 'Pendiente Cambio Pass'] as $status)
                <button 
                    wire:click="setStatusFilter('{{ $status }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-xs font-semibold transition-colors border shadow-sm',
                        'bg-blue-100 border-blue-200 text-blue-700 dark:bg-blue-900/40 dark:border-blue-800 dark:text-blue-300' => $statusFilter === $status,
                        'bg-white border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700' => $statusFilter !== $status,
                    ])
                >
                    @if($status === 'Activos') <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1.5"></span>
                    @elseif($status === 'Bloqueados') <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1.5"></span>
                    @elseif($status === 'Pendiente Cambio Pass') <span class="inline-block w-2 h-2 rounded-full bg-amber-500 mr-1.5"></span>
                    @endif
                    {{ $status }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Users Table -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <th class="py-3 px-2 font-semibold">Usuario</th>
                        <th class="py-3 px-2 font-semibold">Rol</th>
                        {{-- <th class="py-3 px-2 font-semibold text-center">Estado</th> --}}
                        <th class="py-3 px-2 font-semibold">Último Acceso</th>
                        <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($users as $user)
                        <tr wire:key="{{ $user->id }}" class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors">
                            <td class="py-4 px-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-zinc-500">
                                        {{ $user->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-zinc-900 dark:text-white">
                                            {{ $user->name }}
                                            @if($user->role === 'STUDENT') <span class="text-zinc-400 text-xs font-normal">(Alumno)</span> @endif
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $user->email }}
                                            @if($user->phone) · <span class="italic">{{ $user->phone }}</span> @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-2">
                                @php
                                    $roleColor = match($user->role) {
                                        'ADMIN' => 'blue',
                                        'TEACHER' => 'blue',
                                        'PARENT' => 'purple',
                                        'STUDENT' => 'green',
                                        default => 'zinc'
                                    };
                                    $roleLabel = match($user->role) {
                                        'ADMIN' => 'Administrativo',
                                        'TEACHER' => 'Maestro',
                                        'PARENT' => 'Padre/Tutor',
                                        'STUDENT' => 'Alumno',
                                        default => $user->role
                                    };
                                @endphp
                                <flux:badge color="{{ $roleColor }}" size="sm">{{ $roleLabel }}</flux:badge>
                            </td>
                            {{-- <td class="py-4 px-2">
                                <div class="flex items-center gap-2">
                                    @if($user->status === 'ACTIVE')
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                        <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Activo</span>
                                    @elseif($user->status === 'FORCE_PASSWORD_CHANGE')
                                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Cambio Pass Requerido</span>
                                    @elseif($user->status === 'BLOCKED')
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        <span class="text-xs font-medium text-red-600 dark:text-red-400">Bloqueado</span>
                                    @endif
                                </div>
                            </td> --}}
                            <td class="py-4 px-2 text-zinc-500 dark:text-zinc-400">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca' }}
                            </td>
                            <td class="py-4 px-2 text-right">
                                <div class="flex justify-end gap-1">
                                    <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editUser('{{ $user->id }}')" />
                                    
                                    @if($user->status === 'FORCE_PASSWORD_CHANGE')
                                        <flux:button variant="ghost" size="sm" icon="key" class="text-blue-500" disabled />
                                    @else
                                        <flux:button variant="ghost" size="sm" icon="key" wire:click="resetPassword('{{ $user->id }}')" />
                                    @endif

                                    @if($user->id !== auth()->id())
                                        @if($user->status === 'BLOCKED')
                                            <flux:button variant="ghost" size="sm" icon="lock-open" class="text-green-500" wire:click="toggleBlock('{{ $user->id }}')" />
                                        @else
                                            <flux:button variant="ghost" size="sm" icon="lock-closed" class="text-red-500/50 hover:text-red-500" wire:click="toggleBlock('{{ $user->id }}')" />
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500 italic">No se encontraron usuarios coincidentes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-sm text-zinc-500">
            <div>Mostrando {{ $users->count() }} de {{ $totalUsersCount }} usuarios</div>
            <div>{{ $users->links() }}</div>
        </div>
    </div>

    <!-- Protocol footer -->
    {{-- <div class="p-6 rounded-xl border border-blue-100 bg-blue-50 dark:border-blue-900/30 dark:bg-blue-900/10 flex items-start gap-4">
        <flux:icon icon="information-circle" class="text-blue-600 dark:text-blue-400 mt-1" />
        <div class="space-y-1">
            <flux:heading size="sm" class="text-blue-900 dark:text-blue-300 font-bold">Protocolo de Seguridad</flux:heading>
            <flux:text size="sm" class="text-blue-700 dark:text-blue-400 leading-relaxed">
                Las contraseñas generadas temporalmente tienen una validez de 24 horas. Los usuarios deben estar físicamente presentes o verificados telefónicamente antes de realizar un desbloqueo manual.
            </flux:text>
        </div>
    </div> --}}

    <!-- User Modal -->
    <flux:modal wire:model="showUserModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $userId ? 'Editar Usuario' : 'Añadir Nuevo Usuario' }}</flux:heading>

            <form wire:submit="saveUser" class="space-y-4">
                <flux:input 
                    label="Nombre completo" 
                    wire:model="userName" 
                    placeholder="Juan Pérez" 
                    class="uppercase"
                    x-on:input="
                        let start = $el.selectionStart;
                        let end = $el.selectionEnd;
                        $el.value = $el.value.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                        $el.setSelectionRange(start, end);
                    "
                />
                <flux:input label="Correo electrónico" wire:model="userEmail" type="email" placeholder="email@escuela.edu.mx" />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:select label="Rol" wire:model.live="userRole">
                        <option value="TEACHER">Maestro</option>
                        <option value="ADMIN">Administrativo</option>
                        <option value="PARENT">Padre/Tutor</option>
                    </flux:select>
                    
                    @if($userRole === 'PARENT')
                        <flux:input label="Teléfono" wire:model="userPhone" placeholder="123 456 7890" />
                    @endif
                </div>

                @if($userRole === 'PARENT')
                    <flux:input 
                        label="Ocupación" 
                        wire:model="userOccupation" 
                        placeholder="Ej. Ingeniero, Comerciante..." 
                        class="uppercase"
                        x-on:input="
                            let start = $el.selectionStart;
                            let end = $el.selectionEnd;
                            $el.value = $el.value.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                            $el.setSelectionRange(start, end);
                        "
                    />
                @endif

                <div id="user-password-field" class="flex items-end gap-2">
                    <flux:input 
                        label="{{ $userId ? 'Cambiar Contraseña (dejar vacío para mantener)' : 'Contraseña' }}" 
                        wire:model="userPassword" 
                        type="{{ $showPassword ? 'text' : 'password' }}" 
                        placeholder="{{ $userId ? 'Nuevo password...' : 'Password para el usuario...' }}"
                        class="flex-1"
                    />
                    <flux:button variant="ghost" icon="{{ $showPassword ? 'eye-slash' : 'eye' }}" wire:click="$toggle('showPassword')" class="mb-0.5" />
                </div>

                <style>
                    /* Target common Tailwind error classes rendered by the input component inside this wrapper */
                    #user-password-field .text-red-600, #user-password-field .text-red-500, #user-password-field .text-red-700 {
                        display: none !important;
                    }
                </style>

                <div role="alert" aria-live="polite" aria-atomic="true" class="mt-3 text-sm font-medium text-red-500 dark:text-red-400" data-flux-error="">
                    @error('userPassword')
                        <svg class="shrink-0 [:where(&amp;)]:size-5 inline" data-flux-icon="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $message }}
                    @enderror
                </div>

                <div class="flex gap-2 pt-4">
                    <flux:spacer />
                    <flux:button wire:click="$set('showUserModal', false)">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">{{ $userId ? 'Actualizar' : 'Guardar' }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

