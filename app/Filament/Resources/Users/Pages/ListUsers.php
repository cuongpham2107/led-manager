<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Tạo tài khoản người dùng mới')
                ->modalDescription('Nhập thông tin nhân viên, vai trò phân quyền và mật khẩu đăng nhập.')
                ->modalWidth(Width::ThreeExtraLarge),
        ];
    }
}
