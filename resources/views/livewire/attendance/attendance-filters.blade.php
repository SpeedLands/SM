<?php
use Livewire\Volt\Component;
use App\Models\Cycle;
use App\Models\ClassGroup;

new class extends Component {
    public $date;
    public $cycle_id;
    public $grade;
    public $group_id;

    public function getCyclesProperty()
    {
        return Cycle::orderBy('start_date', 'desc')->get();
    }

    public function getGroupsProperty()
    {
        if (!$this->cycle_id || !$this->grade) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->cycle_id)
            ->where('grade', $this->grade)
            ->get();
    }

    public function updated($property)
    {
        $this->dispatch('filters-updated', [
            'date' => $this->date,
            'cycle_id' => $this->cycle_id,
            'grade' => $this->grade,
            'group_id' => $this->group_id,
        ]);
    }
}; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm transition-all mb-6">
    <flux:field>
        <flux:label>Fecha</flux:label>
        <flux:input type="date" wire:model.live="date" />
    </flux:field>

    <flux:field>
        <flux:label>Ciclo</flux:label>
        <flux:select wire:model.live="cycle_id">
            @foreach($this->cycles as $cycle)
                <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
            @endforeach
        </flux:select>
    </flux:field>

    <flux:field>
        <flux:label>Grado</flux:label>
        <flux:select wire:model.live="grade">
            <option value="">Selecciona Grado</option>
            <option value="1º">1º Secundaria</option>
            <option value="2º">2º Secundaria</option>
            <option value="3º">3º Secundaria</option>
        </flux:select>
    </flux:field>

    <flux:field>
        <flux:label>Grupo / Sección</flux:label>
        <flux:select wire:model.live="group_id" :disabled="!$grade">
            <option value="">Selecciona Grupo</option>
            @foreach($this->groups as $group)
                <option value="{{ $group->id }}">Sección {{ $group->section }}</option>
            @endforeach
        </flux:select>
    </flux:field>
</div>
