# Filament 4 Code Migration Guide

## 📚 Official Documentation Links
- **Forms**: https://filamentphp.com/docs/4.x/forms/overview
- **Tables**: https://filamentphp.com/docs/4.x/tables/overview
- **Custom Data**: https://filamentphp.com/docs/4.x/tables/custom-data
- **Editing Records**: https://filamentphp.com/docs/4.x/resources/editing-records
- **V4 Beta Release**: https://filamentphp.com/content/alexandersix-all-about-the-filament-v4-beta-release

**⚠️ Important**: This guide focuses on code changes and new features between Filament 3.x and 4.x

---

## 🔄 1. Namespace Changes

### Actions Namespace Migration
```php
// ❌ Filament 3.x
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

// ✅ Filament 4.x
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
```

### Form/Schema Namespace Split
```php
// ❌ Filament 3.x - Everything in Forms
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

// ✅ Filament 4.x - Split between Forms and Schemas
use Filament\Schemas\Schema;                    // For form/infolist declaration
use Filament\Schemas\Components\Section;        // Layout components
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;        // Form fields stay in Forms
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
```

---

## 🔧 2. Method Name Changes

### Table Methods
```php
// ❌ Filament 3.x
public function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->actions([                    // Old method
            EditAction::make(),
            ViewAction::make(),
        ])
        ->bulkActions([               // Old method
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
}

// ✅ Filament 4.x
public function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->recordActions([             // New method name
            EditAction::make(),
            ViewAction::make(),
        ])
        ->toolbarActions([            // New method name
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
}
```

### Form Declaration
```php
// ❌ Filament 3.x
use Filament\Forms\Form;

public static function form(Form $form): Form
{
    return $form->schema([...]);
}

// ✅ Filament 4.x
use Filament\Schemas\Schema;

public static function form(Schema $schema): Schema
{
    return $schema->schema([...]);
}

// Also for infolists
public static function infolist(Schema $schema): Schema
{
    return $schema->schema([...]);
}
```

### Custom Pages with Forms
```php
// ✅ Filament 4.x - Custom Page with Forms
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;  // Layout components
use Filament\Forms\Components\TextInput;  // Form field components
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class CustomPage extends Page implements HasForms
{
    use InteractsWithForms;
    
    public ?array $data = [];
    
    public function form(Schema $schema): Schema  // Schema, not Form!
    {
        return $schema
            ->schema([
                Section::make('Settings')
                    ->schema([
                        TextInput::make('name')->required(),
                        
                        // Reactive fields - use closure without type hint
                        Toggle::make('is_active')
                            ->visible(fn ($get) => $get('type') === 'premium'), // No Forms\Get type hint
                            
                        // ...
                    ]),
            ])
            ->statePath('data');
    }
    
    public function mount(): void
    {
        $this->form->fill();  // This calls makeSchema() internally
    }
}
```

---

## 📁 3. File Structure Changes

### Resource File Location
```bash
# ❌ Filament 3.x Structure
app/Filament/Resources/
├── UserResource.php              # Main resource file OUTSIDE
└── UserResource/                 # Folder with same name
    ├── Pages/
    │   ├── CreateUser.php
    │   ├── EditUser.php
    │   └── ListUsers.php
    └── RelationManagers/

# ✅ Filament 4.x Structure  
app/Filament/Resources/
└── User/                         # Main folder (no "Resource" suffix)
    ├── UserResource.php          # Main resource file INSIDE folder
    ├── Pages/
    │   ├── CreateUser.php
    │   ├── EditUser.php
    │   ├── ListUsers.php
    │   └── ViewUser.php          # New view page option
    ├── Schemas/                  # New folder for forms/infolists
    │   ├── UserForm.php
    │   └── UserInfolist.php
    └── Tables/                   # New folder for table configuration
        └── UserTable.php
```

---

## 🎯 4. Type Declaration Updates

### Navigation Icon Type
```php
// ❌ Filament 3.x
protected static ?string $navigationIcon = 'heroicon-o-users';
protected static ?string $navigationGroup = 'Management';

// ✅ Filament 4.x - Requires enum support
use BackedEnum;

protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
protected static string|\UnitEnum|null $navigationGroup = 'Management';

// Or using Heroicon enum
use Filament\Support\Icons\Heroicon;
protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
```

---

## 🆕 5. New Features with Code Examples

### 5.1 Nested Resources
Allows editing child resources within parent context:

```bash
# Create nested resource
php artisan make:filament-resource Team/Member --nested
```

```php
// In TeamResource
public static function getRelations(): array
{
    return [
        MembersRelationManager::class,
    ];
}

// URL structure: /teams/{team}/members/{member}/edit
```

### 5.2 Static Table Data (Non-Eloquent)
Display data from arrays, APIs, or any non-database source:

```php
use Illuminate\Pagination\LengthAwarePaginator;

public function table(Table $table): Table
{
    return $table
        ->records(function (?string $search, int $page, int $recordsPerPage) {
            // Fetch from API
            $response = Http::get('https://api.example.com/data', [
                'search' => $search,
                'page' => $page,
                'per_page' => $recordsPerPage,
            ]);
            
            return new LengthAwarePaginator(
                $response['data'],
                $response['total'],
                $recordsPerPage,
                $page
            );
        })
        ->columns([
            TextColumn::make('name'),
            TextColumn::make('email'),
        ])
        ->searchable()
        ->paginated();
}

// Or simple array data
->records([
    ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
])
```

### 5.3 Multi-Factor Authentication
Built-in MFA support:

```php
// In AdminPanelProvider
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->mfa()                    // Enable MFA
        ->mfaRequired()           // Make it mandatory
        ->mfaMethods([
            'authenticator',      // Google Authenticator
            'email',             // Email codes
        ]);
}
```

### 5.4 Table Summaries
Add summary rows to tables:

```php
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Count;

TextColumn::make('amount')
    ->money('USD')
    ->summarize([
        Sum::make()
            ->label('Total')
            ->money('USD'),
        Average::make()
            ->label('Average'),
        Count::make()
            ->label('Transactions'),
    ])
```

### 5.5 Independent Section Saving
Save form sections independently:

```php
Section::make('Settings')
    ->schema([...])
    ->footerActions([
        Action::make('saveSettings')
            ->action(function ($data, $record) {
                $record->settings()->update($data);
                Notification::make()
                    ->title('Settings saved')
                    ->success()
                    ->send();
            })
    ])
```

---

## 📝 6. Form Component Updates

### Correct Component Usage
```php
// ✅ Filament 4.x - Correct namespaces
use Filament\Schemas\Components\Section;      // Layout
use Filament\Schemas\Components\Tabs;         // Layout
use Filament\Schemas\Components\Grid;         // Layout
use Filament\Forms\Components\TextInput;      // Field
use Filament\Forms\Components\Select;         // Field
use Filament\Forms\Components\Repeater;       // Field

Section::make('User Information')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->required(),
            TextInput::make('last_name')
                ->required(),
        ]),
        Repeater::make('addresses')
            ->schema([
                TextInput::make('street'),
                TextInput::make('city'),
            ])
            ->collapsible()
            ->cloneable(),
    ])
```

### Tab Component Import
```php
// ❌ Filament 3.x
use Filament\Resources\Pages\ListRecords\Tab;

// ✅ Filament 4.x
use Filament\Schemas\Components\Tabs\Tab;
```

---

## 🔨 7. Edit Page Lifecycle Hooks

### Data Mutation Methods
```php
class EditProduct extends EditRecord
{
    // Transform data before filling form
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['price'] = $data['price'] / 100; // cents to dollars
        return $data;
    }
    
    // Transform data before saving
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['price'] = $data['price'] * 100; // dollars to cents
        return $data;
    }
    
    // Custom save logic
    protected function handleRecordUpdate($record, array $data): Model
    {
        $record->update($data);
        $record->syncTags($data['tags'] ?? []);
        return $record;
    }
}
```

### Lifecycle Hooks
```php
protected function beforeFill(): void { }
protected function afterFill(): void { }
protected function beforeValidate(): void { }
protected function afterValidate(): void { }
protected function beforeSave(): void 
{
    if (!$this->record->canBeEdited()) {
        $this->halt(); // Stop saving
    }
}
protected function afterSave(): void 
{
    Notification::make()
        ->title('Saved successfully')
        ->success()
        ->send();
}
```

---

## ⚡ 8. Action Modal Forms

### Correct Namespace for Action Forms
```php
// In table actions
Action::make('updateStatus')
    ->form([
        // Use Forms\Components for fields
        \Filament\Forms\Components\Select::make('status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->required(),
        
        // Use Schemas\Components for layout
        \Filament\Schemas\Components\Section::make()
            ->schema([
                \Filament\Forms\Components\Textarea::make('reason')
                    ->required(),
            ])
    ])
    ->action(function ($record, array $data) {
        $record->update($data);
    })
```

---

## 🚨 9. Common Migration Errors

### Error 1: Class not found
```php
// ❌ Error: Class "Filament\Forms\Components\Section" not found
use Filament\Forms\Components\Section;

// ✅ Fix: Use correct namespace
use Filament\Schemas\Components\Section;
```

### Error 2: Method not found
```php
// ❌ Error: Call to undefined method actions()
->actions([...])

// ✅ Fix: Use new method name
->recordActions([...])
```

### Error 3: Type error with Get callbacks
```php
// ❌ Error: Argument #1 ($get) must be of type Filament\Forms\Get, Filament\Schemas\Components\Utilities\Get given
->visible(fn (Forms\Get $get) => $get('field') === 'value')

// ✅ Fix: Remove type hint from closure
->visible(fn ($get) => $get('field') === 'value')
```

### Error 4: Navigation icon type error
```php
// ❌ Error: Type error with navigation icon
protected static ?string $navigationIcon = 'heroicon-o-users';

// ✅ Fix: Update type declaration
use BackedEnum;
protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
```

---

## 🛠️ 10. Migration Checklist

### Quick Migration Steps
1. **Update Composer**
   ```bash
   composer require filament/filament:"^4.0"
   ```

2. **Clear Caches**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan filament:clear-cached-components
   ```

3. **Update Imports** (Find & Replace)
   - `Filament\Tables\Actions\` → `Filament\Actions\`
   - `Filament\Forms\Form` → `Filament\Schemas\Schema`
   - `Filament\Forms\Components\Section` → `Filament\Schemas\Components\Section`

4. **Update Methods**
   - `->actions()` → `->recordActions()`
   - `->bulkActions()` → `->toolbarActions()`
   - `form(Form $form)` → `form(Schema $schema)`

5. **Move Resource Files**
   - Move main resource file inside its folder

6. **Update Type Declarations**
   - Add `BackedEnum` support for icons

---

## 📖 Additional Resources

### Official Documentation
- [Filament 4.x Docs](https://filamentphp.com/docs/4.x)
- [Migration Guide](https://filamentphp.com/docs/4.x/upgrade)
- [GitHub Issues](https://github.com/filamentphp/filament/issues)

### Status
- **Version**: 4.x Beta
- **Laravel**: 12.x+
- **PHP**: 8.3+
- **Last Updated**: January 2025