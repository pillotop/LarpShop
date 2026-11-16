<?php

return [
    'payment' => [
        'subject' => 'LarpShop - Payment confirmed',
        'greeting' => 'Payment confirmed',
        'order' => 'Order #:id',
        'amount' => 'Amount: :amount :currency',
        'method' => 'Payment method: :method',
        'success' => 'Your payment was successful.',
        'action' => 'View order history',
        'database_body' => 'Payment of :amount for Order #:id has been confirmed.',
    ],
    'fulfillment' => [
        'title' => 'Order #:id — :status',
        'body' => ':store updated items in Order #:id to :status.',
    ],
    'bell' => [
        'label' => 'Notifications',
        'mark_all_read' => 'Mark all as read',
        'empty' => 'You are all caught up — no notifications yet.',
        'unread_count' => ':count unread notifications',
    ],
];
