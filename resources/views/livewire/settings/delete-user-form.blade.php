<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    public function deleteUser(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        Auth::logout();

        $user->delete();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <flux:heading size="lg">Eliminar Cuenta</flux:heading>
        <flux:text>Una vez que se elimine su cuenta, todos sus recursos y datos se eliminarán de forma permanente. Antes de eliminar su cuenta, descargue cualquier dato o información que desee conservar.</flux:text>
    </header>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger">Eliminar Cuenta</flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" class="max-w-lg">
        <form wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">¿Está seguro de que desea eliminar su cuenta?</flux:heading>
                <flux:text>Una vez que se elimine su cuenta, todos sus recursos y datos se eliminarán de forma permanente. Por favor, introduzca su contraseña para confirmar que desea eliminar su cuenta de forma permanente.</flux:text>
            </div>

            <flux:input 
                wire:model="password" 
                :label="__('Contraseña')" 
                type="password" 
                placeholder="{{ __('Contraseña') }}"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger">Eliminar Cuenta</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
