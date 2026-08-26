<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin tài khoản người dùng')
                    ->description('Quản lý thông tin đăng nhập, phân quyền và kho làm việc')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Họ và tên')
                                ->required()
                                ->placeholder('VD: Đỗ Quang Huy'),
                            TextInput::make('email')
                                ->label('Địa chỉ Email')
                                ->email()
                                ->required()
                                ->placeholder('VD: huy.do@ledmanager.com'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('password')
                                ->label('Mật khẩu')
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->placeholder('••••••••'),
                            TextInput::make('phone')
                                ->label('Số điện thoại')
                                ->tel()
                                ->placeholder('VD: 0912 345 678'),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('warehouse_id')
                                ->label('Kho làm việc chính')
                                ->relationship('warehouse', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('roles')
                                ->label('Vai trò & Phân quyền')
                                ->relationship('roles', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable(),
                        ]),
                        Toggle::make('is_active')
                            ->label('Tài khoản đang hoạt động')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
