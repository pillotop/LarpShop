<?php

return [
    'payment' => [
        'subject' => 'LarpShop - Xác nhận thanh toán',
        'greeting' => 'Xác nhận thanh toán',
        'order' => 'Đơn hàng #:id',
        'amount' => 'Số tiền: :amount :currency',
        'method' => 'Phương thức thanh toán: :method',
        'success' => 'Bạn đã thanh toán thành công.',
        'action' => 'Xem lịch sử đơn hàng',
        'database_body' => 'Thanh toán :amount cho Đơn hàng #:id đã được xác nhận.',
    ],
    'fulfillment' => [
        'title' => 'Đơn hàng #:id — :status',
        'body' => ':store đã cập nhật trạng thái sản phẩm trong Đơn hàng #:id sang :status.',
    ],
    'bell' => [
        'label' => 'Thông báo',
        'mark_all_read' => 'Đánh dấu tất cả đã đọc',
        'empty' => 'Chưa có thông báo mới nào.',
        'unread_count' => ':count thông báo chưa đọc',
    ],
];
