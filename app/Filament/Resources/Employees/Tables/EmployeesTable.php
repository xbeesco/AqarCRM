<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('الهاتف الأول')
                    ->searchable(),

                TextColumn::make('secondary_phone')
                    ->label('الهاتف الثاني')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'employee' => 'موظف',
                        'admin' => 'مدير',
                        'super_admin' => 'مدير النظام',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'employee' => 'success',
                        default => 'secondary'
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn ($record) => EmployeeResource::canEdit($record)),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                // Super admin can see all
                if ($user->type === 'super_admin') {
                    return $query;
                }

                // Admin can see all employees but no super admins
                if ($user->type === 'admin') {
                    return $query->where('type', '!=', 'super_admin');
                }

                // Employees can only see themselves
                if ($user->type === 'employee') {
                    return $query->where('id', $user->id);
                }

                // Others see nothing
                return $query->whereRaw('1 = 0');
            });
    }
}
