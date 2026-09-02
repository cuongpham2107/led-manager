<?php

return [

    'menu' => [
        'switch_to' => 'Chuyển sang',
        'switch' => 'Chuyển đổi',
        'manage' => 'Quản lý tài khoản liên kết',
        'password' => 'Mật khẩu của tài khoản bạn muốn chuyển sang',
        'confirm_heading' => 'Chuyển sang :account',
        'confirm_description' => 'Xác nhận quyền sở hữu bằng cách nhập mật khẩu của tài khoản này.',
    ],

    'impersonate' => [
        'label' => 'Đóng vai',
    ],

    'banner' => [
        'message' => 'Bạn đang đăng nhập với tư cách :impersonated (đang đóng vai từ :impersonator).',
        'switch_back' => 'Quay lại tài khoản chính',
    ],

    'developer_logins' => [
        'heading' => 'Đăng nhập nhanh cho nhà phát triển (:environment)',
    ],

    'linked_accounts' => [
        'title' => 'Tài khoản liên kết',
        'subheading' => 'Các tài khoản bạn có thể chuyển đổi nhanh từ menu "Chuyển sang". Sử dụng tài khoản quyền hạn thấp hơn khi làm việc thường ngày và chỉ chuyển sang tài khoản đầy đủ khi cần thiết.',
        'empty_heading' => 'Chưa có tài khoản liên kết nào',
        'empty_description' => 'Liên kết tài khoản hiện có hoặc tạo tài khoản phụ để chuyển đổi qua lại mà không cần đăng xuất.',
        'link_description' => 'Nhập thông tin đăng nhập của tài khoản bạn muốn liên kết. Cả hai tài khoản sẽ có thể chuyển đổi qua lại.',
        'create_description' => 'Tạo tài khoản mới và liên kết với tài khoản bạn đang đăng nhập.',
        'fields' => [
            'label' => 'Nhãn hiển thị',
            'account' => 'Tài khoản',
            'email' => 'Địa chỉ email',
            'name' => 'Họ và tên',
            'account_password' => 'Mật khẩu tài khoản đó',
            'new_password' => 'Mật khẩu',
            'requires_password' => 'Yêu cầu mật khẩu khi chuyển đổi',
            'requires_password_help' => 'Khuyến nghị bật khi chuyển sang tài khoản có quyền hạn cao hơn.',
            'linked_at' => 'Đã liên kết lúc',
        ],
        'actions' => [
            'link' => 'Liên kết tài khoản có sẵn',
            'create' => 'Tạo tài khoản phụ',
            'rename' => 'Đổi tên nhãn',
            'unlink' => 'Huỷ liên kết',
        ],
        'notifications' => [
            'linked' => 'Đã liên kết tài khoản thành công.',
            'created' => 'Đã tạo và liên kết tài khoản phụ thành công.',
            'unlinked' => 'Đã huỷ liên kết tài khoản.',
            'invalid_credentials' => 'Thông tin đăng nhập không chính xác.',
        ],
    ],

    'reasons' => [
        'impersonation' => 'Đóng vai người dùng',
        'impersonation_ended' => 'Kết thúc đóng vai',
        'linked_account' => 'Tài khoản liên kết',
        'developer_login' => 'Đăng nhập nhanh cho Dev',
    ],

    'errors' => [
        'not_linked' => 'Bạn không thể chuyển sang tài khoản này.',
        'invalid_password' => 'Mật khẩu không chính xác.',
        'while_impersonating' => 'Không thể chuyển đổi tài khoản khi đang trong chế độ đóng vai.',
        'cannot_impersonate' => 'Bạn không có quyền đóng vai người dùng này.',
        'developer_logins_disabled' => 'Tính năng đăng nhập nhanh cho Dev không khả dụng.',
        'feature_disabled' => 'Tính năng này hiện đang bị tắt.',
    ],

];
